<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your username and profile picture')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="space-y-3">
                <flux:label>{{ __('Profile Picture') }}</flux:label>

                <div class="flex items-center gap-4">
                    @if($avatar)
                        <img src="{{ $avatar->temporaryUrl() }}" alt="{{ __('Avatar preview') }}" class="size-16 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-zinc-700" />
                    @elseif(auth()->user()->avatarUrl())
                        <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ __('Current avatar') }}" class="size-16 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-zinc-700" />
                    @else
                        <div class="flex size-16 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            {{ auth()->user()->initials() }}
                        </div>
                    @endif

                    <div class="flex-1 space-y-2">
                        <input type="file" wire:model="avatar" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-zinc-600 file:mr-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-zinc-200 dark:text-zinc-300 dark:file:bg-zinc-800 dark:hover:file:bg-zinc-700" />
                        <flux:text class="text-xs text-zinc-500">{{ __('JPG, PNG, or WEBP. Max 2MB.') }}</flux:text>
                    </div>
                </div>

                @error('avatar')
                    <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                @enderror

                @if(auth()->user()->avatar_path)
                    <flux:button type="button" variant="ghost" wire:click="removeAvatar" wire:confirm="{{ __('Remove your profile picture?') }}" class="!text-red-500">
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
