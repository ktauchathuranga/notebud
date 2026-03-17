@section('meta_title', 'Log in - Notebud')
@section('meta_description', 'Log in to Notebud to access your notes, uploads, and shared study resources.')
@section('meta_image', url('/og-image.png'))
@section('canonical_url', route('login'))
@section('meta_keywords', 'notebud login, student notes login, file sharing login')

<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to Notebud')" :description="__('Enter your username and password')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form id="login-form" method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            @error('cf-turnstile-response')
                <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
            @enderror

            <!-- Username -->
            <flux:input
                name="username"
                :label="__('Username')"
                :value="old('username')"
                type="text"
                required
                autofocus
                autocomplete="username"
                placeholder="johndoe"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        <div class="text-sm text-center text-zinc-600 dark:text-zinc-400">
            <flux:link :href="route('recovery.recover')" wire:navigate>{{ __('Forgot password? Use a recovery code') }}</flux:link>
        </div>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>{{ __('Don\'t have an account?') }}</span>
                <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>
