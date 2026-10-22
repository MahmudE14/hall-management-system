<?php

namespace App\Http\Requests\Accommodation;

use App\Models\Room;
use App\Models\Seat;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Seat::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => [
                'required',
                'string',
                'size:1',
                Rule::unique('seats', 'label')->where('room_id', $this->roomId()),
            ],
        ];
    }

    private function roomId(): ?int
    {
        $room = $this->route('room');

        return $room instanceof Room ? $room->id : null;
    }
}
