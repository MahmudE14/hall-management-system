<?php

namespace App\Http\Requests\Accommodation;

use App\Models\Seat;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        $seat = $this->seat();

        return $seat !== null
            && ($this->user()?->can('update', $seat) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $seat = $this->seat();

        return [
            'label' => [
                'required',
                'string',
                'size:1',
                Rule::unique('seats', 'label')
                    ->where('room_id', $seat?->room_id)
                    ->ignore($seat?->id),
            ],
        ];
    }

    private function seat(): ?Seat
    {
        $seat = $this->route('seat');

        return $seat instanceof Seat ? $seat : null;
    }
}
