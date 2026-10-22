<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tutor\StoreTutorRequest;
use App\Http\Requests\Tutor\UpdateTutorRequest;
use App\Models\Block;
use App\Models\Role;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TutorController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', TutorProfile::class);

        return Inertia::render('house-tutors/index', [
            'tutors' => TutorProfile::query()
                ->with(['user', 'blocks'])
                ->orderBy('id')
                ->get()
                ->map(fn (TutorProfile $tutorProfile) => $this->serializeTutor($tutorProfile))
                ->values()
                ->all(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', TutorProfile::class);

        return Inertia::render('house-tutors/create', [
            'blocks' => $this->blockOptions(),
        ]);
    }

    public function edit(TutorProfile $tutorProfile): Response
    {
        Gate::authorize('update', $tutorProfile);

        return Inertia::render('house-tutors/edit', [
            'tutor' => $this->serializeTutor($tutorProfile, includeBlockIds: true),
            'blocks' => $this->blockOptions(),
        ]);
    }

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

            $tutorProfile = $user->tutorProfile()->create([
                'designation' => $data['designation'],
                'department' => $data['department'],
                'phone' => $data['phone'],
                'is_active' => true,
            ]);

            $tutorProfile->blocks()->sync($data['block_ids'] ?? []);

            $user->roles()->attach($tutorRole);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tutor created.')]);

        return to_route('house-tutors.index');
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

            if (array_key_exists('block_ids', $data)) {
                $tutorProfile->blocks()->sync($data['block_ids']);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tutor updated.')]);

        return to_route('house-tutors.index');
    }

    public function deactivate(TutorProfile $tutorProfile): RedirectResponse
    {
        Gate::authorize('deactivate', $tutorProfile);

        $tutorProfile->update(['is_active' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tutor deactivated.')]);

        return to_route('house-tutors.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTutor(TutorProfile $tutorProfile, bool $includeBlockIds = false): array
    {
        $tutorProfile->loadMissing(['user', 'blocks']);

        $blocks = $tutorProfile->blocks->sortBy('name')->values();

        return [
            'id' => $tutorProfile->id,
            'name' => $tutorProfile->user->name,
            'email' => $tutorProfile->user->email,
            'designation' => $tutorProfile->designation,
            'department' => $tutorProfile->department,
            'phone' => $tutorProfile->phone,
            'is_active' => $tutorProfile->is_active,
            'blocks' => $blocks
                ->map(fn (Block $block) => $block->only(['id', 'name', 'code']))
                ->all(),
            ...($includeBlockIds ? ['block_ids' => $blocks->map(fn (Block $block) => $block->id)->all()] : []),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, code: string}>
     */
    private function blockOptions(): array
    {
        return Block::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Block $block) => [
                'id' => $block->id,
                'name' => $block->name,
                'code' => $block->code,
            ])
            ->all();
    }
}
