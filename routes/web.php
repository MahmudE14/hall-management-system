<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('blocks', BlockController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::resource('rooms', RoomController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::get('students', [StudentController::class, 'index'])->name('students.index');
    Route::post('rooms/{room}/seats', [SeatController::class, 'store'])->name('seats.store');
    Route::put('seats/{seat}', [SeatController::class, 'update'])->name('seats.update');
});

Route::middleware(['auth', 'verified', 'role:super_admin,provost,hall_office_staff'])->group(function () {
    Route::get('registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::post('registrations/{studentProfile}/approve', [RegistrationController::class, 'approve'])->name('registrations.approve');
    Route::post('registrations/{studentProfile}/reject', [RegistrationController::class, 'reject'])->name('registrations.reject');
    Route::post('registrations/{studentProfile}/reconsider', [RegistrationController::class, 'reconsider'])->name('registrations.reconsider');
});

Route::middleware(['auth', 'verified', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.roles.update');
    Route::patch('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
});

require __DIR__.'/settings.php';
