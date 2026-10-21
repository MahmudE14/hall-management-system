<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', Rule::exists(Role::class, 'name')],
            'registration_status' => ['nullable', Rule::in(array_column(RegistrationStatus::cases(), 'value'))],
        ]);

        $users = User::query()
            ->with(['roles:id,name', 'studentProfile'])
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search) => $query->where(
                    fn (Builder $query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas(
                            'studentProfile',
                            fn (Builder $query) => $query->where('registration_number', 'like', "%{$search}%"),
                        ),
                ),
            )
            ->when(
                $filters['role'] ?? null,
                fn (Builder $query, string $role) => $query->whereHas(
                    'roles',
                    fn (Builder $query) => $query->where('name', $role),
                ),
            )
            ->when(
                $filters['registration_status'] ?? null,
                fn (Builder $query, string $status) => $query->whereHas(
                    'studentProfile',
                    fn (Builder $query) => $query->where('registration_status', $status),
                ),
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'roles' => $user->roles->pluck('name')->values()->all(),
                'student_profile' => $user->studentProfile?->only([
                    'registration_number',
                    'department',
                    'session',
                    'phone',
                    'registration_status',
                    'residency_status',
                ]),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => Role::query()->orderBy('name')->pluck('name')->all(),
            'registrationStatuses' => array_column(RegistrationStatus::cases(), 'value'),
        ]);
    }
}
