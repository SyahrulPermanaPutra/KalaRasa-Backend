<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
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
            'password' => $this->passwordRules(),
            'phone' => [
                'required',
                'string',
                'min:10',
                'max:20',
                'regex:/^0[0-9]+$/',
            ],
            'gender' => ['required', 'in:pria,wanita'],
            'birthdate' => ['required', 'date', 'before:today'],
        ], [
            'email.unique' => 'Akun dengan email ini sudah terdaftar.',
            'birthdate.before' => 'Tanggal lahir harus sebelum hari ini.',
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'phone' => $input['phone'],
            'gender' => $input['gender'],
            'birthdate' => $input['birthdate'],
        ]);
    }
}
