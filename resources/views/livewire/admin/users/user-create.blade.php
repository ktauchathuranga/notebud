<div>
    <div class="mx-auto w-full max-w-3xl">
        <div class="mb-6 flex items-center gap-3">
            <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate icon="arrow-left" />
            <flux:heading size="xl">{{ __('Create User') }}</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="username" :label="__('Username')" type="text" required autofocus />

            <div>
                <flux:label>{{ __('Role') }}</flux:label>
                <select wire:model="role" class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <option value="user">{{ __('User') }}</option>
                    <option value="admin">{{ __('Admin') }}</option>
                </select>
                @error('role')
                    <div class="mt-2 text-sm text-red-500">{{ $message }}</div>
                @enderror
            </div>

            <flux:input wire:model="password" :label="__('Password')" type="password" required />
            <flux:input wire:model="password_confirmation" :label="__('Confirm Password')" type="password" required />
            <flux:input wire:model="storage_quota_mb" :label="__('Storage Quota (MB, optional)')" type="number" min="1" step="0.01" placeholder="20" />
            <flux:text class="text-xs text-zinc-500">{{ __('Leave empty to use the global default quota.') }}</flux:text>

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit">{{ __('Create User') }}</flux:button>
                <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</div>
