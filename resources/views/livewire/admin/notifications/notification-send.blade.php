<div>
    <div class="mx-auto w-full max-w-4xl space-y-6">
        <flux:heading size="xl">{{ __('Admin Notifications') }}</flux:heading>

        @if(session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700 dark:border-green-900/60 dark:bg-green-900/20 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit="send" class="space-y-6">
            <flux:input wire:model="title" :label="__('Title')" type="text" required maxlength="120" />
            <flux:textarea wire:model="message" :label="__('Message')" rows="5" required />

            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="priority" :label="__('Priority')">
                    <flux:select.option value="info">{{ __('Info') }}</flux:select.option>
                    <flux:select.option value="success">{{ __('Success') }}</flux:select.option>
                    <flux:select.option value="warning">{{ __('Warning') }}</flux:select.option>
                    <flux:select.option value="danger">{{ __('Danger') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="target" :label="__('Target')">
                    <flux:select.option value="all">{{ __('All users (including admins)') }}</flux:select.option>
                    <flux:select.option value="selected">{{ __('Selected users') }}</flux:select.option>
                </flux:select>
            </div>

            <flux:input wire:model="action_url" :label="__('Action URL (optional)')" type="url" placeholder="https://example.com/path" />

            @if($target === 'selected')
                <div class="space-y-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:input wire:model.live.debounce.300ms="userSearch" placeholder="Search users..." icon="magnifying-glass" />

                    @error('selectedUserIds')
                        <div class="text-sm text-red-500">{{ $message }}</div>
                    @enderror

                    <div class="max-h-72 space-y-2 overflow-y-auto">
                        @forelse($users as $user)
                            <flux:checkbox wire:model="selectedUserIds" value="{{ $user->id }}" :label="$user->username" />
                        @empty
                            <flux:text class="text-sm text-zinc-500">{{ __('No users match your search.') }}</flux:text>
                        @endforelse
                    </div>
                </div>
            @endif

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit" icon="paper-airplane">
                    {{ __('Send Notification') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
