<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your username and profile picture')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="space-y-4">
                <flux:label>{{ __('Profile Picture') }}</flux:label>

                <div x-data="{ uploading: false }" class="relative flex items-center gap-4 pt-2">
                    <div class="relative">
                        @if($avatar)
                            <img src="{{ $avatar->temporaryUrl() }}" alt="{{ __('Avatar preview') }}" class="size-16 rounded-full object-cover shadow-sm ring-2 ring-zinc-300 ring-offset-2 ring-offset-zinc-50 dark:ring-zinc-100 dark:ring-offset-zinc-900" />
                        @elseif(auth()->user()->avatarUrl())
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ __('Current avatar') }}" class="size-16 rounded-full object-cover shadow-sm ring-2 ring-zinc-300 ring-offset-2 ring-offset-zinc-50 dark:ring-zinc-100 dark:ring-offset-zinc-900" />
                        @else
                            <div class="flex size-16 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-700 shadow-sm ring-2 ring-zinc-300 ring-offset-2 ring-offset-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-100 dark:ring-offset-zinc-900">
                                {{ auth()->user()->initials() }}
                            </div>
                        @endif
                        <div x-show="uploading" class="absolute inset-0 flex items-center justify-center bg-white/70 dark:bg-zinc-900/60 rounded-full">
                            <svg class="size-8 text-zinc-400 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 space-y-2">
                        <input type="file" wire:model="avatar" accept="image/jpeg,image/png,image/webp"
                            class="block w-full text-sm text-zinc-600 file:mr-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-zinc-200 dark:text-zinc-300 dark:file:bg-zinc-800 dark:hover:file:bg-zinc-700"
                            x-on:change="uploading = true"
                            x-on:livewire-upload-finish="uploading = false"
                            x-on:livewire-upload-error="uploading = false"
                            x-on:livewire-upload-cancel="uploading = false"
                        />
                        <flux:text class="text-xs text-zinc-500">{{ __('JPG, PNG, or WEBP. Maximum size: 2MB') }}</flux:text>
                    </div>
                </div>

                @error('avatar')
                    <flux:text class="text-sm text-zinc-500">{{ $message }}</flux:text>
                @enderror

                @if(auth()->user()->avatar_path)
                    <flux:button type="button" variant="ghost" wire:click="removeAvatar" wire:confirm="{{ __('Remove your profile picture?') }}" class="text-red-500! hover:bg-red-50! dark:hover:bg-red-900/20!">
                        {{ __('Remove picture') }}
                    </flux:button>
                @endif
            </div>

            <flux:input wire:model="username" :label="__('Username')" type="text" required autofocus autocomplete="username" />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
