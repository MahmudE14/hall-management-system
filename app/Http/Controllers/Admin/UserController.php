<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRolesRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
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
            ->through(fn (User $user) => $this->serializeUser($user));

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => Role::query()->orderBy('name')->pluck('name')->all(),
            'registrationStatuses' => array_column(RegistrationStatus::cases(), 'value'),
        ]);
    }

    public function activate(User $user): RedirectResponse
    {
        $user->update(['is_active' => true]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account activated.')]);

        return to_route('admin.users.edit', $user);
    }

    public function deactivate(User $user): RedirectResponse
    {
        $user->update(['is_active' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account deactivated.')]);

        return to_route('admin.users.edit', $user);
    }

    public function edit(User $user): Response
    {
        $user->load(['roles:id,name', 'studentProfile']);

        return Inertia::render('admin/users/edit', [
            'user' => $this->serializeUser($user),
            'roles' => Role::query()
                ->whereIn('name', Role::DOCUMENTED)
                ->orderBy('name')
                ->pluck('name')
                ->all(),
        ]);
    }

    public function updateRoles(UpdateUserRolesRequest $request, User $user): RedirectResponse
    {
        $roleIds = Role::query()
            ->whereIn('name', $request->validated('roles'))
            ->pluck('id');

        $user->roles()->sync($roleIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Roles updated.')]);

        return to_route('admin.users.edit', $user);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'is_active' => $user->is_active,
            'roles' => $user->roles->pluck('name')->values()->all(),
            'student_profile' => $user->studentProfile?->only([
                'registration_number',
                'department',
                'session',
                'phone',
                'registration_status',
                'residency_status',
            ]),
        ];
    }
}
