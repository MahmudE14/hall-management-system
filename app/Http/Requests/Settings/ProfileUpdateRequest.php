<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        $profileOwner = $this->route('user');

        return $profileOwner instanceof User && $this->user()->is($profileOwner);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = $this->profileRules($this->user()->id);

        if ($this->user()->hasRole('student')) {
            $rules['email'][] = 'regex:/^[^@\s]+@student\.du\.test$/';
            $rules['phone'] = ['required', 'string', 'regex:/^01[3-9]\d{8}$/'];
        }

        return $rules;
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.regex' => 'Use your @student.du.test email address.',
            'phone.regex' => 'Enter a valid Bangladeshi mobile number.',
        ];
    }
}
