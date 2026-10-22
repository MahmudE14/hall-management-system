<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRolesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(Role::DOCUMENTED),
                Rule::exists(Role::class, 'name'),
            ],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $roles = $this->input('roles');

                if (! is_array($roles) || ! in_array('student', $roles, true)) {
                    return;
                }

                $user = $this->route('user');

                if ($user instanceof User && ! $user->studentProfile()->exists()) {
                    $validator->errors()->add(
                        'roles',
                        __('A student profile is required to assign the student role.'),
                    );
                }
            },
        ];
    }
}
