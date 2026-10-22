<?php

namespace App\Http\Requests\Complaints;

use App\Enums\ComplaintCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(ComplaintCategory::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn (): bool => $this->user()?->studentProfile?->activeSeatAllocation()->doesntExist() ?? true),
            ],
        ];
    }
}
