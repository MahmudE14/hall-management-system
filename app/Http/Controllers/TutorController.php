<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tutor\StoreTutorRequest;
use App\Http\Requests\Tutor\UpdateTutorRequest;
use App\Models\Role;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TutorController extends Controller
{
    public function store(StoreTutorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $tutorRole = Role::query()->firstOrCreate(['name' => 'house_tutor']);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Str::random(40),
                'is_active' => true,
            ]);

            $user->tutorProfile()->create([
                'designation' => $data['designation'],
                'department' => $data['department'],
                'phone' => $data['phone'],
                'is_active' => true,
            ]);

            $user->roles()->attach($tutorRole);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tutor created.')]);

        return to_route('dashboard');
    }

    public function update(UpdateTutorRequest $request, TutorProfile $tutorProfile): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $tutorProfile): void {
            $tutorProfile->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            $tutorProfile->update([
                'designation' => $data['designation'],
                'department' => $data['department'],
                'phone' => $data['phone'],
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tutor updated.')]);

        return to_route('dashboard');
    }

    public function deactivate(TutorProfile $tutorProfile): RedirectResponse
    {
        Gate::authorize('deactivate', $tutorProfile);

        $tutorProfile->update(['is_active' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tutor deactivated.')]);

        return to_route('dashboard');
    }
}
