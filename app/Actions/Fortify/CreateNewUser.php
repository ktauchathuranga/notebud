<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        $captchaRules = app()->environment('testing')
            ? ['nullable']
            : ['required', new Recaptcha];

        Validator::make($input, [
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'g-recaptcha-response' => $captchaRules,
        ], [
            'g-recaptcha-response.required' => 'Please complete the reCAPTCHA verification.',
        ])->validate();

        return User::create([
            'username' => $input['username'],
            'password' => $input['password'],
        ]);
    }
}
