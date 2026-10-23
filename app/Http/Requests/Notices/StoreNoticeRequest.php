<?php

namespace App\Http\Requests\Notices;

use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use App\Models\Notice;
use App\Rules\PdfDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! Gate::forUser($user)->allows('create', Notice::class)) {
            return false;
        }

        $rawCategory = $this->input('category');

        if ($rawCategory === null || $rawCategory === '') {
            return true;
        }

        $category = is_string($rawCategory)
            ? NoticeCategory::tryFrom($rawCategory)
            : null;

        if ($category === null) {
            return true;
        }

        return Gate::forUser($user)->allows('createInCategory', [Notice::class, $category]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'category' => ['required', Rule::enum(NoticeCategory::class)],
            'status' => ['required', Rule::enum(NoticeStatus::class)],
            'is_pinned' => ['sometimes', 'boolean'],
            'attachment' => ['nullable', 'file', new PdfDocument],
        ];
    }
}
