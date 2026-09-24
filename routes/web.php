<?php

use App\Http\Controllers\Admin\RegistrationRequestController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest authentication & registration flow
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('home');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// Authenticated user routes (guarded with approval status)
Route::middleware(['auth', 'approved'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Meetings Module
    Route::resource('meetings', \App\Http\Controllers\MeetingController::class);
    Route::patch('/meetings/{meeting}/cancel', [\App\Http\Controllers\MeetingController::class, 'cancel'])->name('meetings.cancel');
    Route::post('/meetings/{meeting}/rsvp', [\App\Http\Controllers\MeetingController::class, 'rsvp'])->name('meetings.rsvp');

    // Calendar Hub
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CalendarController::class, 'index'])->name('index');
        Route::get('/feed', [\App\Http\Controllers\CalendarController::class, 'feed'])->name('feed');
        Route::post('/', [\App\Http\Controllers\CalendarController::class, 'store'])->name('store');
        Route::patch('/{type}/{id}/time', [\App\Http\Controllers\CalendarController::class, 'updateTime'])->name('update-time');
        Route::delete('/{type}/{id}', [\App\Http\Controllers\CalendarController::class, 'destroy'])->name('destroy');
    });

    // Super Admin registration request management
    Route::middleware('super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/registration-requests', [RegistrationRequestController::class, 'index'])->name('registration-requests.index');
        Route::patch('/registration-requests/{user}/approve', [RegistrationRequestController::class, 'approve'])->name('registration-requests.approve');
        Route::patch('/registration-requests/{user}/reject', [RegistrationRequestController::class, 'reject'])->name('registration-requests.reject');
    });
});
