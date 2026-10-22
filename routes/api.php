<?php

use App\Http\Controllers\Api\v1\AdminRegistrationController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\CalendarController;
use App\Http\Controllers\Api\v1\ClientController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\DeviceController;
use App\Http\Controllers\Api\v1\EventController;
use App\Http\Controllers\Api\v1\LookupController;
use App\Http\Controllers\Api\v1\MeetingController;
use App\Http\Controllers\Api\v1\MeetingNoteController;
use App\Http\Controllers\Api\v1\NotificationController;
use App\Http\Controllers\Api\v1\PublicBookingController;
use App\Http\Controllers\Api\v1\RoomController;
use App\Http\Controllers\Api\v1\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Schedio Mobile & External REST API (v1)
|--------------------------------------------------------------------------
| Base URL: /api/v1/*
*/

Route::prefix('v1')->group(function () {

    // 1. Public Authentication & Registration Endpoints (No Bearer Token)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::get('/auth/registration-options', [AuthController::class, 'registrationOptions']);

    Route::prefix('public/booking')->group(function () {
        Route::get('/{username}', [PublicBookingController::class, 'show']);
        Route::get('/{username}/slots', [PublicBookingController::class, 'slots']);
        Route::post('/{username}', [PublicBookingController::class, 'book']);
    });

    // 2. Protected Endpoints (Requires Laravel Sanctum Bearer Token)
    Route::middleware('auth:sanctum')->group(function () {

        // Session & Current User
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Executive Dashboard & Consolidated Lookups
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/lookups', [LookupController::class, 'index']);

        // Push Notification Device Tokens
        Route::post('/devices', [DeviceController::class, 'store']);
        Route::delete('/devices/{token}', [DeviceController::class, 'destroy']);

        // Meetings & Collaborative Workspaces
        Route::get('/meetings', [MeetingController::class, 'index']);
        Route::post('/meetings', [MeetingController::class, 'store']);
        Route::get('/meetings/{meeting}', [MeetingController::class, 'show']);
        Route::put('/meetings/{meeting}', [MeetingController::class, 'update']);
        Route::patch('/meetings/{meeting}/cancel', [MeetingController::class, 'cancel']);
        Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy']);
        Route::post('/meetings/{meeting}/rsvp', [MeetingController::class, 'rsvp']);

        // Meeting Notes
        Route::get('/meetings/{meeting}/notes', [MeetingController::class, 'notes']);
        Route::post('/meetings/{meeting}/notes', [MeetingController::class, 'storeNote']);
        Route::get('/meeting-notes/{id}', [MeetingNoteController::class, 'show']);
        Route::put('/meeting-notes/{id}', [MeetingNoteController::class, 'update']);
        Route::delete('/meeting-notes/{id}', [MeetingNoteController::class, 'destroy']);

        // Meeting Rooms & Focus Pods
        Route::get('/rooms', [RoomController::class, 'index']);
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::get('/rooms/{room}', [RoomController::class, 'show']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

        // Company Events, Holidays, & Training
        Route::get('/events', [EventController::class, 'index']);
        Route::post('/events', [EventController::class, 'store']);
        Route::get('/events/{event}', [EventController::class, 'show']);
        Route::put('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);

        // Calendar & Availability Engine
        Route::get('/calendar/feed', [CalendarController::class, 'feed']);
        Route::post('/availability/check', [CalendarController::class, 'checkAvailability']);
        Route::get('/availability/suggestions', [CalendarController::class, 'suggestions']);

        // Clients Pipeline (BD / CRM)
        Route::get('/clients', [ClientController::class, 'index']);
        Route::post('/clients', [ClientController::class, 'store']);
        Route::get('/clients/{client}', [ClientController::class, 'show']);
        Route::put('/clients/{client}', [ClientController::class, 'update']);
        Route::delete('/clients/{client}', [ClientController::class, 'destroy']);
        Route::patch('/clients/{client}/status', [ClientController::class, 'updateStatus']);
        Route::get('/clients/{client}/meetings', [ClientController::class, 'meetings']);

        // Actionable Tasks
        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::get('/tasks/{task}', [TaskController::class, 'show']);
        Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);

        // Notifications & Reminders Center
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/notifications/{id}/unread', [NotificationController::class, 'markAsUnread']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::get('/notifications/preferences', [NotificationController::class, 'getPreferences']);
        Route::post('/notifications/preferences', [NotificationController::class, 'updatePreferences']);
        Route::post('/notifications/test-push', [NotificationController::class, 'testPush']);

        // Administrative Registration Requests
        Route::get('/admin/registration-requests', [AdminRegistrationController::class, 'index']);
        Route::get('/admin/registration-requests/{id}/options', [AdminRegistrationController::class, 'options']);
        Route::patch('/admin/registration-requests/{id}/approve', [AdminRegistrationController::class, 'approve']);
        Route::patch('/admin/registration-requests/{id}/reject', [AdminRegistrationController::class, 'reject']);

        // Core Lookups
        Route::get('/departments', [LookupController::class, 'departments']);
        Route::get('/users', [LookupController::class, 'users']);
    });
});
