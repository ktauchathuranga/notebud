<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Rules\Recaptcha;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::loginView(fn () => view('livewire.auth.login'));
        Fortify::registerView(fn () => view('livewire.auth.register'));

        // Validate reCAPTCHA on login
        Fortify::authenticateUsing(function (Request $request) {
            Validator::make($request->all(), [
                'g-recaptcha-response' => ['required', new Recaptcha],
            ], [
                'g-recaptcha-response.required' => 'Please complete the reCAPTCHA verification.',
            ])->validate();

            $user = \App\Models\User::where('username', $request->username)->first();

            if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                return $user;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
