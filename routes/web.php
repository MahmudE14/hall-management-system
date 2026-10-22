<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SeatAllocationController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TutorController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('blocks', BlockController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::resource('rooms', RoomController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::get('students', [StudentController::class, 'index'])->name('students.index');
    Route::get('students/{studentProfile}', [StudentController::class, 'show'])->name('students.show');
    Route::post('students/{studentProfile}/allocation', [SeatAllocationController::class, 'store'])->name('allocations.store');
    Route::patch('allocations/{seatAllocation}/vacate', [SeatAllocationController::class, 'vacate'])->name('allocations.vacate');
    Route::post('rooms/{room}/seats', [SeatController::class, 'store'])->name('seats.store');
    Route::put('seats/{seat}', [SeatController::class, 'update'])->name('seats.update');
    Route::get('house-tutors', [TutorController::class, 'index'])->name('house-tutors.index');
    Route::get('house-tutors/create', [TutorController::class, 'create'])->name('house-tutors.create');
    Route::post('house-tutors', [TutorController::class, 'store'])->name('house-tutors.store');
    Route::get('house-tutors/{tutorProfile}/edit', [TutorController::class, 'edit'])->name('house-tutors.edit');
    Route::put('house-tutors/{tutorProfile}', [TutorController::class, 'update'])->name('house-tutors.update');
    Route::patch('house-tutors/{tutorProfile}/deactivate', [TutorController::class, 'deactivate'])->name('house-tutors.deactivate');
    Route::get('complaints', [ComplaintController::class, 'index'])->name('complaints.index');
});

Route::middleware(['auth', 'verified', 'approved.student'])->group(function () {
    Route::get('complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
    Route::post('complaints', [ComplaintController::class, 'store'])->name('complaints.store');
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
