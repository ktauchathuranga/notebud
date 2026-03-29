<div class="flex items-center justify-between px-3 py-2">
    <flux:heading size="sm">{{ __('Notifications') }}</flux:heading>
    @if($notifications->isNotEmpty())
        <div class="flex items-center gap-2">
            @if($unreadCount > 0)
                <flux:link wire:click="markAllAsRead" class="text-xs cursor-pointer">{{ __('Mark all as read') }}</flux:link>
            @endif
            <flux:link wire:click="clearAll" class="text-xs cursor-pointer text-red-500">
                {{ __('Clear all') }}
            </flux:link>
        </div>
    @endif
</div>
<flux:menu.separator />

@forelse($notifications as $notification)
    @php
        $isShareRequestNotification = isset($notification->data['shared_by']);
    @endphp
    <div
        x-data="{ loading: false }"
        @click="loading = true; setTimeout(() => loading = false, 500);"
        wire:click="{{ $isShareRequestNotification ? "openNotification('{$notification->id}')" : "markAsRead('{$notification->id}')" }}"
        class="relative px-3 py-2 cursor-pointer transition-colors duration-200
            hover:bg-zinc-50 dark:hover:bg-zinc-800
            {{ !$notification->read_at ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
        :class="loading ? 'bg-amber-100 dark:bg-amber-900/30 opacity-70' : ''"
    >
        <template x-if="loading">
            <div class="absolute inset-0 flex items-center justify-center bg-amber-100/80 dark:bg-amber-900/60 rounded z-10">
                <svg class="animate-spin h-6 w-6 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
            </div>
        </template>
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
        @elseif(isset($notification->data['title']))
            @php
                $priority = $notification->data['priority'] ?? 'info';

                $priorityStyles = match ($priority) {
                    'success' => [
                        'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                        'icon' => 'check-circle',
                        'iconClass' => 'text-emerald-500',
                        'label' => __('Success'),
                    ],
                    'warning' => [
                        'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                        'icon' => 'exclamation-triangle',
                        'iconClass' => 'text-amber-500',
                        'label' => __('Warning'),
                    ],
                    'danger' => [
                        'badge' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                        'icon' => 'x-circle',
                        'iconClass' => 'text-red-500',
                        'label' => __('Danger'),
                    ],
                    default => [
                        'badge' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                        'icon' => 'information-circle',
                        'iconClass' => 'text-sky-500',
                        'label' => __('Info'),
                    ],
                };
            @endphp

            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <flux:icon name="{{ $priorityStyles['icon'] }}" class="size-4 {{ $priorityStyles['iconClass'] }}" />
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $priorityStyles['badge'] }}">
                        {{ $priorityStyles['label'] }}
                    </span>
                </div>
                <flux:text class="text-sm font-medium">{{ $notification->data['title'] }}</flux:text>
                <flux:text class="text-sm">{{ $notification->data['message'] }}</flux:text>
                @if(!empty($notification->data['sent_by']))
                    <flux:text class="text-xs text-zinc-400">{{ __('Sent by') }} {{ $notification->data['sent_by'] }}</flux:text>
                @endif
                @if(!empty($notification->data['action_url']))
                    <a href="{{ $notification->data['action_url'] }}" target="_blank" rel="noopener noreferrer" class="text-xs text-blue-500 hover:underline">
                        {{ __('Open Link') }}
                    </a>
                @endif
            </div>
        @endif
        <flux:text class="text-xs text-zinc-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</flux:text>
    </div>
@empty
    <div class="px-3 py-6 text-center">
        <flux:text class="text-sm text-zinc-400">{{ __('No notifications') }}</flux:text>
    </div>
@endforelse
