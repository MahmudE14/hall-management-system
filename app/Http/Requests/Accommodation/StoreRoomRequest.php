<?php

namespace App\Http\Requests\Accommodation;

use App\Models\Block;
use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Room::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'block_id' => ['required', 'integer', Rule::exists(Block::class, 'id')],
            'room_no' => [
                'required',
                'string',
                'max:20',
                Rule::unique('rooms', 'room_no')->where('block_id', $this->input('block_id')),
            ],
            'floor' => ['required', 'integer', 'min:0', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:255'],
        ];
    }
}
