<?php

namespace App\Http\Requests\Tutor;

use App\Models\Block;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TutorProfile::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'designation' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'block_ids' => ['sometimes', 'array'],
            'block_ids.*' => ['integer', Rule::exists(Block::class, 'id')],
        ];
    }
}
