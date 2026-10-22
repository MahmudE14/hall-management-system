<?php

namespace App\Http\Requests\Accommodation;

use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $room = $this->room();

        return $room !== null
            && ($this->user()?->can('update', $room) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $room = $this->room();

        return [
            'room_no' => [
                'required',
                'string',
                'max:20',
                Rule::unique('rooms', 'room_no')
                    ->where('block_id', $room?->block_id)
                    ->ignore($room?->id),
            ],
            'floor' => ['required', 'integer', 'min:0', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:255'],
        ];
    }

    private function room(): ?Room
    {
        $room = $this->route('room');

        return $room instanceof Room ? $room : null;
    }
}
