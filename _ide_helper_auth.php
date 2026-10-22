<?php

/**
 * Custom Intelephense / IDE helper for Laravel Auth, Request, and Eloquent helpers.
 * This file is purely for static analysis and IDE indexing, and is not executed.
 */

namespace Illuminate\Contracts\Auth {
    /**
     * @mixin \App\Models\User
     *
     * @property int $id
     * @property int|null $company_id
     * @property int|null $department_id
     * @property string $name
     * @property string $email
     * @property string $role
     * @property string $role_label
     * @property string $approval_status
     * @property bool $is_active
     * @property string|null $timezone
     * @property string|null $avatar_url
     * @property string|null $title
     * @property string|null $phone
     * @property string|null $rejection_reason
     * @property-read \App\Models\Company|null $company
     * @property-read \App\Models\Department|null $department
     * @property-read \App\Models\User|null $approver
     *
     * @method bool isDeveloper()
     * @method bool isCompanySuperAdmin()
     * @method bool isSuperAdmin()
     * @method bool isManager()
     * @method bool isBD()
     * @method bool isApproved()
     * @method bool isPending()
     * @method bool isRejected()
     * @method bool canLogin()
     */
    interface Authenticatable
    {
    }
}

namespace Illuminate\Support\Facades {
    /**
     * @method static \App\Models\User|null user()
     * @method static int|string|null id()
     * @method static bool check()
     * @method static bool guest()
     * @method static void login(\Illuminate\Contracts\Auth\Authenticatable $user, bool $remember = false)
     * @method static void logout()
     */
    class Auth
    {
    }
}

namespace Illuminate\Http {
    /**
     * @method \App\Models\User|null user($guard = null)
     */
    class Request
    {
    }
}

namespace {
    /**
     * Get the available auth instance or the authenticated user.
     *
     * @param string|null $guard
     * @return \Illuminate\Contracts\Auth\Factory|\Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard|\App\Models\User
     */
    function auth($guard = null)
    {
    }
}
