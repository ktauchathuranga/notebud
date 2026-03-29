<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Recover account')" :description="__('Use your username and one recovery code to set a new password')" />

    <form wire:submit="recover" class="flex flex-col gap-6">
        <flux:input
            wire:model="username"
            :label="__('Username')"
            type="text"
            required
            autofocus
            autocomplete="username"
            placeholder="Username"
        />

        <flux:input
            wire:model="recovery_code"
            :label="__('Recovery code')"
            type="text"
            required
            placeholder="ABCD-EFGH-IJKL-MNOP"
        />

        <flux:input
            wire:model="password"
            :label="__('New password')"
            type="password"
            required
            autocomplete="new-password"
            viewable
        />

        <flux:input
            wire:model="password_confirmation"
            :label="__('Confirm new password')"
            type="password"
            required
            autocomplete="new-password"
            viewable
        />

        <flux:button variant="primary" type="submit" class="w-full">
            {{ __('Reset password with recovery code') }}
        </flux:button>
    </form>

    <div class="space-x-1 text-center text-sm rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Remembered it?') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>
</div>
