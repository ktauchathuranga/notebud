@section('meta_title', 'Register - Notebud')
@section('meta_description', 'Create a Notebud account to write markdown notes, upload files, and share with classmates by username.')
@section('meta_image', url('/og-image.png'))
@section('canonical_url', route('register'))
@section('meta_keywords', 'notebud register, create student account, notes sharing app')

<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create a Notebud account')" :description="__('Pick a username and password to get started')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form id="register-form" method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
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
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
