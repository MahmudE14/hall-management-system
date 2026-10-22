<?php

namespace App\Http\Requests\Accommodation;

use App\Models\SeatAllocation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VacateSeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        $allocation = $this->route('seatAllocation');

        return $allocation instanceof SeatAllocation
            && ($this->user()?->can('update', $allocation) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vacated_at' => ['required', 'date'],
            'vacancy_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
