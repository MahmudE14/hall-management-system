<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\Block;
use App\Models\Seat;
use App\Models\SeatAllocation;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    /** @var array<int, string> */
    private const array DIRECTORY_ROLES = ['super_admin', 'provost', 'hall_office_staff'];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', StudentProfile::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'block' => ['nullable', 'integer', Rule::exists('blocks', 'id')],
            'department' => ['nullable', 'string', 'max:255'],
            'session' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_column(RegistrationStatus::cases(), 'value'))],
        ]);

        $user = $request->user();

        $students = StudentProfile::query()
            ->with(['user', 'activeSeatAllocation.seat.room.block'])
            ->when(
                ! $user->hasAnyRole(...self::DIRECTORY_ROLES),
                fn (Builder $query) => $query->where('user_id', $user->id),
            )
            ->when(
                isset($filters['block']),
                fn (Builder $query) => $query->whereHas(
                    'activeSeatAllocation.seat.room',
                    fn (Builder $query) => $query->where('block_id', $filters['block']),
                ),
            )
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search) => $query->where(
                    fn (Builder $query) => $query
                        ->whereHas(
                            'user',
                            fn (Builder $query) => $query->where('name', 'like', "%{$search}%"),
                        )
                        ->orWhere('registration_number', 'like', "%{$search}%"),
                ),
            )
            ->when(
                $filters['department'] ?? null,
                fn (Builder $query, string $department) => $query->where('department', $department),
            )
            ->when(
                $filters['session'] ?? null,
                fn (Builder $query, string $session) => $query->where('session', $session),
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status) => $query->where('registration_status', $status),
            )
            ->orderBy('registration_number')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (StudentProfile $student) => $this->serializeStudent($student));

        return Inertia::render('students/index', [
            'students' => $students,
            'filters' => [
                'search' => $filters['search'] ?? null,
                'block' => isset($filters['block']) ? (int) $filters['block'] : null,
                'department' => $filters['department'] ?? null,
                'session' => $filters['session'] ?? null,
                'status' => $filters['status'] ?? null,
            ],
            'blocks' => Block::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->all(),
            'departments' => StudentProfile::query()
                ->distinct()
                ->orderBy('department')
                ->pluck('department')
                ->values()
                ->all(),
            'sessions' => StudentProfile::query()
                ->distinct()
                ->orderBy('session')
                ->pluck('session')
                ->values()
                ->all(),
            'statuses' => array_column(RegistrationStatus::cases(), 'value'),
        ]);
    }

    public function show(StudentProfile $studentProfile): Response
    {
        Gate::authorize('view', $studentProfile);

        $studentProfile->load('user');

        $activeAllocation = $studentProfile->seatAllocations()
            ->where('active', true)
            ->with(['seat.room.block'])
            ->first();

        $canAllocate = Gate::allows('create', SeatAllocation::class);

        $availableSeats = $canAllocate && $activeAllocation === null
            && $studentProfile->registration_status === RegistrationStatus::Approved
                ? Seat::query()
                    ->whereDoesntHave(
                        'seatAllocations',
                        fn (Builder $query) => $query->where('active', true),
                    )
                    ->with('room.block')
                    ->orderBy('room_id')
                    ->orderBy('label')
                    ->get()
                    ->map(fn (Seat $seat) => [
                        'id' => $seat->id,
                        'label' => $seat->label,
                        'room_no' => $seat->room->room_no,
                        'block' => $seat->room->block?->name,
                    ])
                    ->values()
                    ->all()
                : [];

        return Inertia::render('students/show', [
            'student' => $this->serializeStudent($studentProfile),
            'canAllocate' => $canAllocate,
            'availableSeats' => $availableSeats,
            'activeAllocation' => $activeAllocation === null ? null : [
                'id' => $activeAllocation->id,
                'allocated_at' => $activeAllocation->allocated_at->toDateString(),
                'seat' => [
                    'id' => $activeAllocation->seat->id,
                    'label' => $activeAllocation->seat->label,
                ],
                'room' => [
                    'id' => $activeAllocation->seat->room->id,
                    'room_no' => $activeAllocation->seat->room->room_no,
                ],
                'block' => $activeAllocation->seat->room->block?->only(['id', 'name', 'code']),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeStudent(StudentProfile $studentProfile): array
    {
        /** @var User $user */
        $user = $studentProfile->user;

        $currentRoom = $studentProfile->activeSeatAllocation?->seat?->room;

        return [
            'id' => $studentProfile->id,
            'user_id' => $studentProfile->user_id,
            'name' => $user->name,
            'email' => $user->email,
            'registration_number' => $studentProfile->registration_number,
            'department' => $studentProfile->department,
            'session' => $studentProfile->session,
            'phone' => $studentProfile->phone,
            'registration_status' => $studentProfile->registration_status->value,
            'residency_status' => $studentProfile->residency_status->value,
            'block' => $currentRoom?->block?->only(['id', 'name', 'code']),
            'current_room' => $currentRoom === null ? null : [
                'id' => $currentRoom->id,
                'room_no' => $currentRoom->room_no,
            ],
            'allocation_history' => [],
            'allocation_history_available' => false,
        ];
    }
}
