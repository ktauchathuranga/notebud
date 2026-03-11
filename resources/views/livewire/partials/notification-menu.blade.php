<div class="flex items-center justify-between px-3 py-2">
    <flux:heading size="sm">{{ __('Notifications') }}</flux:heading>
    @if($unreadCount > 0)
        <flux:link wire:click="markAllAsRead" class="text-xs cursor-pointer">{{ __('Mark all as read') }}</flux:link>
    @endif
</div>
<flux:menu.separator />

@forelse($notifications as $notification)
    <div
        wire:click="markAsRead('{{ $notification->id }}')"
        class="cursor-pointer px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 {{ !$notification->read_at ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
    >
        @if(isset($notification->data['shared_by']))
            <flux:text class="text-sm">
                <span class="font-medium">{{ $notification->data['shared_by'] }}</span>
                {{ __('shared a') }} {{ $notification->data['type'] }}:
                <span class="font-medium">{{ $notification->data['name'] }}</span>
            </flux:text>
            @if(!empty($notification->data['message']))
                <flux:text class="text-xs text-zinc-400 mt-0.5">"{{ $notification->data['message'] }}"</flux:text>
            @endif
        @elseif(isset($notification->data['responded_by']))
            <flux:text class="text-sm">
                <span class="font-medium">{{ $notification->data['responded_by'] }}</span>
                {{ $notification->data['status'] === 'accepted' ? __('accepted') : __('rejected') }}
                {{ __('your') }} {{ $notification->data['type'] }}:
                <span class="font-medium">{{ $notification->data['name'] }}</span>
            </flux:text>
        @endif
        <flux:text class="text-xs text-zinc-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</flux:text>
    </div>
@empty
    <div class="px-3 py-6 text-center">
        <flux:text class="text-sm text-zinc-400">{{ __('No notifications') }}</flux:text>
    </div>
@endforelse
