<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:super_admin,provost,hall_office_staff'])->group(function () {
    Route::get('registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::post('registrations/{studentProfile}/approve', [RegistrationController::class, 'approve'])->name('registrations.approve');
    Route::post('registrations/{studentProfile}/reject', [RegistrationController::class, 'reject'])->name('registrations.reject');
    Route::post('registrations/{studentProfile}/reconsider', [RegistrationController::class, 'reconsider'])->name('registrations.reconsider');
});

Route::middleware(['auth', 'verified', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
});

require __DIR__.'/settings.php';
