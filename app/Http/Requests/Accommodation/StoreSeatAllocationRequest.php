<?php

namespace App\Http\Requests\Accommodation;

use App\Models\Seat;
use App\Models\SeatAllocation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeatAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SeatAllocation::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'seat_id' => ['required', 'integer', Rule::exists(Seat::class, 'id')],
        ];
    }
}
