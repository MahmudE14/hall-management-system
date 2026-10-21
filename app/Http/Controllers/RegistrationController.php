<?php

namespace App\Http\Controllers;

use App\Actions\Registration\DecideRegistration;
use App\Enums\RegistrationStatus;
use App\Http\Requests\RejectRegistrationRequest;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in([
                RegistrationStatus::Pending->value,
                RegistrationStatus::Rejected->value,
            ])],
        ]);

        $status = RegistrationStatus::from($validated['status'] ?? RegistrationStatus::Pending->value);

        return Inertia::render('registrations/index', [
            'status' => $status->value,
            'registrations' => StudentProfile::query()
                ->where('registration_status', $status)
                ->with(['user', 'registrationDecisions.actor'])
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function approve(
        Request $request,
        StudentProfile $studentProfile,
        DecideRegistration $decideRegistration,
    ): RedirectResponse {
        $decideRegistration->handle(
            $studentProfile,
            $request->user(),
            RegistrationStatus::Approved,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Registration approved.')]);

        return to_route('registrations.index');
    }

    public function reject(
        RejectRegistrationRequest $request,
        StudentProfile $studentProfile,
        DecideRegistration $decideRegistration,
    ): RedirectResponse {
        $decideRegistration->handle(
            $studentProfile,
            $request->user(),
            RegistrationStatus::Rejected,
            $request->validated('reason'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Registration rejected.')]);

        return to_route('registrations.index');
    }
}
