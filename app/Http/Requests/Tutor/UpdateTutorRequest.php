<?php

namespace App\Http\Requests\Tutor;

use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tutorProfile = $this->route('tutorProfile');

        return $tutorProfile instanceof TutorProfile
            && ($this->user()?->can('update', $tutorProfile) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tutorProfile = $this->route('tutorProfile');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore(
                    $tutorProfile instanceof TutorProfile ? $tutorProfile->user_id : null,
                ),
            ],
            'designation' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
        ];
    }
}
