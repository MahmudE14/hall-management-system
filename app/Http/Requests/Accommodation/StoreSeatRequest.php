<?php

namespace App\Http\Requests\Accommodation;

use App\Models\Room;
use App\Models\Seat;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('label')) {
                    return;
                }

                $room = $this->route('room');

                if (! $room instanceof Room) {
                    return;
                }

                if ($room->seats()->count() >= $room->capacity) {
                    $validator->errors()->add(
                        'label',
                        __('This room already has the maximum number of seats allowed by its capacity.'),
                    );
                }
            },
        ];
    }

    private function roomId(): ?int
    {
        $room = $this->route('room');

        return $room instanceof Room ? $room->id : null;
    }
}
