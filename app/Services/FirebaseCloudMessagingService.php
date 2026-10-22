<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseCloudMessagingService
{
    protected ?string $projectId;
    protected ?string $credentialsPath;
    protected ?string $credentialsJson;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', env('FIREBASE_PROJECT_ID', 'schedio-app-bc57b'));
        $this->credentialsPath = config('services.firebase.credentials_path', env('FIREBASE_CREDENTIALS', storage_path('app/firebase/service-account.json')));
        $this->credentialsJson = config('services.firebase.credentials_json', env('FIREBASE_CREDENTIALS_JSON'));
    }

    /**
     * Check if Firebase server credentials are configured on this environment.
     */
    public function isConfigured(): bool
    {
        if (!empty($this->credentialsJson)) {
            return true;
        }

        return !empty($this->credentialsPath) && file_exists($this->credentialsPath);
    }

    /**
     * Send push notification to a specific FCM device token via FCM HTTP v1 API.
     */
    public function sendToToken(string $token, string $title, string $body, array $data = [], array $options = []): array
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            Log::info("FCM push skipped: Firebase service account credentials not present on server. Notification: '{$title}' -> {$token}");
            return [
                'success' => false,
                'status' => 'unconfigured',
                'message' => 'Firebase credentials not configured on backend.',
            ];
        }

        $projectId = $this->getProjectId();
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        // Convert all data dictionary values to strings as required by FCM specifications
        $stringData = [];
        foreach ($data as $k => $v) {
            $stringData[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
        }

        if (!isset($stringData['click_action'])) {
            $stringData['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';
        }

        $channelId = $options['channel_id'] ?? 'schedio_high_importance_channel';

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => $channelId,
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'default_vibrate_timings' => true,
                        'notification_priority' => 'PRIORITY_HIGH',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json; UTF-8',
            ])->timeout(10)->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('name'),
                ];
            }

            $resJson = $response->json();
            $errorCode = $resJson['error']['details'][0]['errorCode'] ?? ($resJson['error']['status'] ?? null);

            // If token is expired or unregistered, automatically purge from database
            if ($response->status() === 404 || in_array($errorCode, ['UNREGISTERED', 'NOT_FOUND', 'INVALID_ARGUMENT'], true)) {
                Log::info("Pruning unregistered or expired FCM device token: {$token}");
                DeviceToken::where('token', $token)->delete();
            } else {
                Log::warning("FCM push delivery failed with HTTP {$response->status()}: " . $response->body());
            }

            return [
                'success' => false,
                'http_status' => $response->status(),
                'error' => $resJson['error']['message'] ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error("FCM push delivery exception: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send push notification to all active devices registered to a specific user.
     */
    public function sendToUser(User|int $user, string $title, string $body, array $data = [], array $options = []): int
    {
        $userModel = is_numeric($user) ? User::find($user) : $user;
        if (!$userModel) {
            return 0;
        }

        $tokens = DeviceToken::where('user_id', $userModel->id)->pluck('token');
        if ($tokens->isEmpty()) {
            return 0;
        }

        $sentCount = 0;
        foreach ($tokens as $token) {
            $result = $this->sendToToken($token, $title, $body, $data, $options);
            if (!empty($result['success'])) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Send push notification to multiple users.
     */
    public function sendToUsers(Collection|array $users, string $title, string $body, array $data = [], array $options = []): int
    {
        $sentCount = 0;
        foreach ($users as $user) {
            $sentCount += $this->sendToUser($user, $title, $body, $data, $options);
        }
        return $sentCount;
    }

    /**
     * Send a controlled test push notification to a user email or direct token.
     */
    public function sendTestNotification(string $target): array
    {
        $title = 'Schedio Mobile Alert';
        $body = 'Test push notification from official Schedio backend. Verified on ' . now()->format('M d, Y h:i A') . '.';
        $data = [
            'type' => 'test_push',
            'timestamp' => now()->toIso8601String(),
            'source' => 'schedio_fcm_v1',
        ];

        // Check if target is an email
        if (filter_var($target, FILTER_VALIDATE_EMAIL)) {
            $user = User::withoutGlobalScopes()->where('email', $target)->first();
            if (!$user) {
                return [
                    'success' => false,
                    'message' => "User with email '{$target}' not found in database.",
                ];
            }

            $tokens = DeviceToken::where('user_id', $user->id)->get();
            if ($tokens->isEmpty()) {
                return [
                    'success' => false,
                    'message' => "User {$user->name} ({$user->email}) has no registered device tokens. Log in on mobile first to register token.",
                ];
            }

            $results = [];
            foreach ($tokens as $dt) {
                $results[] = [
                    'token' => substr($dt->token, 0, 16) . '...',
                    'platform' => $dt->platform,
                    'result' => $this->sendToToken($dt->token, $title, $body, $data),
                ];
            }

            return [
                'success' => true,
                'target_user' => $user->name,
                'tokens_count' => $tokens->count(),
                'dispatches' => $results,
            ];
        }

        // Direct token
        $res = $this->sendToToken($target, $title, $body, $data);
        return [
            'success' => $res['success'] ?? false,
            'target_token' => substr($target, 0, 16) . '...',
            'result' => $res,
        ];
    }

    /**
     * Retrieve or generate a Google OAuth2 Bearer access token using Service Account RS256 JWT.
     */
    protected function getAccessToken(): ?string
    {
        $credentials = $this->loadServiceAccountCredentials();
        if (!$credentials) {
            return null;
        }

        $cacheKey = 'schedio_fcm_oauth2_token_' . md5($credentials['client_email'] ?? 'default');

        return Cache::remember($cacheKey, 3300, function () use ($credentials) {
            return $this->requestOAuth2Token($credentials);
        });
    }

    /**
     * Request Google OAuth2 access token via JWT assertion.
     */
    protected function requestOAuth2Token(array $credentials): ?string
    {
        $clientEmail = $credentials['client_email'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;
        $tokenUri = $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token';

        if (!$clientEmail || !$privateKey) {
            Log::error('Firebase service account credentials missing client_email or private_key.');
            return null;
        }

        $now = time();
        $jwtHeader = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $jwtClaim = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $tokenUri,
            'exp' => $now + 3600,
            'iat' => $now,
        ]));

        $unsignedToken = "{$jwtHeader}.{$jwtClaim}";
        $signature = '';

        $success = openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$success) {
            Log::error('Failed to sign Google OAuth2 JWT with Firebase private key.');
            return null;
        }

        $signedJwt = "{$unsignedToken}." . $this->base64UrlEncode($signature);

        try {
            $response = Http::asForm()->timeout(10)->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $signedJwt,
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Google OAuth2 token request rejected: ' . $response->body());
            return null;
        } catch (\Throwable $e) {
            Log::error('Exception requesting Google OAuth2 token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Load service account JSON from file or environment string.
     */
    protected function loadServiceAccountCredentials(): ?array
    {
        if (!empty($this->credentialsJson)) {
            $decoded = json_decode($this->credentialsJson, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (!empty($this->credentialsPath) && file_exists($this->credentialsPath)) {
            $content = file_get_contents($this->credentialsPath);
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function getProjectId(): string
    {
        $credentials = $this->loadServiceAccountCredentials();
        if (!empty($credentials['project_id'])) {
            return $credentials['project_id'];
        }

        return $this->projectId ?: 'schedio-app-bc57b';
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
