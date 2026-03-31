<div>
    <div class="mx-auto w-full max-w-3xl">
        <div class="mb-6 flex items-center gap-3">
            <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate icon="arrow-left" />
            <flux:heading size="xl">{{ __('Create User') }}</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="username" :label="__('Username')" type="text" required autofocus />

            <flux:select wire:model="role" :label="__('Role')">
                <flux:select.option value="user">{{ __('User') }}</flux:select.option>
                <flux:select.option value="admin">{{ __('Admin') }}</flux:select.option>
            </flux:select>

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
