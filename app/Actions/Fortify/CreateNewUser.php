<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'email' => [
                ...$this->emailRules(),
                'regex:/^[^@\s]+@student\.du\.test$/i',
            ],
            'registration_number' => [
                'required',
                'string',
                'digits:10',
                Rule::unique(StudentProfile::class, 'registration_number'),
            ],
            'department' => ['required', 'string', 'max:255'],
            'session' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'password' => $this->passwordRules(),
        ], [
            'email.regex' => 'Use your @student.du.test email address.',
            'email.unique' => 'This email address is already registered.',
            'registration_number.digits' => 'Enter a 10-digit registration number.',
            'registration_number.unique' => 'This registration number is already registered.',
            'phone.regex' => 'Enter a valid Bangladeshi mobile number.',
        ])->validate();

        $studentRole = Role::query()->where('name', 'student')->firstOrFail();

        return DB::transaction(function () use ($input, $studentRole): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'is_active' => true,
            ]);

            $user->studentProfile()->create([
                'registration_number' => $input['registration_number'],
                'department' => $input['department'],
                'session' => $input['session'],
                'phone' => $input['phone'],
            ]);
            $user->roles()->attach($studentRole);

            return $user;
        });
    }
}
