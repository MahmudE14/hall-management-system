<?php

namespace App\Http\Controllers;

use App\Http\Requests\Accommodation\StoreBlockRequest;
use App\Http\Requests\Accommodation\UpdateBlockRequest;
use App\Models\Block;
use App\Models\Hall;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BlockController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Block::class);

        return Inertia::render('blocks/index', [
            'blocks' => Block::query()
                ->withCount('rooms')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Block::class);

        return Inertia::render('blocks/create');
    }

    public function store(StoreBlockRequest $request): RedirectResponse
    {
        Hall::query()->firstOrFail()->blocks()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Block created.')]);

        return to_route('blocks.index');
    }

    public function edit(Block $block): Response
    {
        Gate::authorize('update', $block);

        return Inertia::render('blocks/edit', [
            'block' => $block,
        ]);
    }

    public function update(UpdateBlockRequest $request, Block $block): RedirectResponse
    {
        $block->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Block updated.')]);

        return to_route('blocks.index');
    }
}
