<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="xl">{{ __('User Management') }}</flux:heading>
            <div class="flex items-center gap-3">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search users..." icon="magnifying-glass" class="w-full sm:w-64" />
                <flux:button variant="primary" :href="route('admin.users.create')" wire:navigate icon="plus">
                    {{ __('Create User') }}
                </flux:button>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-xs text-zinc-500">{{ __('Users') }}</flux:text>
                <flux:heading size="lg" class="mt-1">{{ number_format((int) $insights['total_users']) }}</flux:heading>
                <flux:text class="mt-1 text-xs text-zinc-500">
                    {{ __('Admins: :admins | Members: :members', ['admins' => number_format((int) $insights['admin_users']), 'members' => number_format((int) $insights['member_users'])]) }}
                </flux:text>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-xs text-zinc-500">{{ __('Content') }}</flux:text>
                <flux:heading size="lg" class="mt-1">{{ number_format((int) $insights['total_notes']) }} {{ __('notes') }}</flux:heading>
                <flux:text class="mt-1 text-xs text-zinc-500">
                    {{ __('Files: :files', ['files' => number_format((int) $insights['total_files'])]) }}
                </flux:text>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-xs text-zinc-500">{{ __('Storage Used') }}</flux:text>
                <flux:heading size="lg" class="mt-1">{{ \App\Support\StorageQuota::formatBytes((int) $insights['total_storage_used_bytes']) }}</flux:heading>
                <flux:text class="mt-1 text-xs text-zinc-500">
                    {{ __('Avg per user: :avg', ['avg' => \App\Support\StorageQuota::formatBytes((int) $insights['average_storage_per_user_bytes'])]) }}
                </flux:text>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-xs text-zinc-500">{{ __('Quota Health') }}</flux:text>
                <flux:heading size="lg" class="mt-1">{{ number_format((int) $insights['over_quota_users']) }}</flux:heading>
                <flux:text class="mt-1 text-xs text-zinc-500">{{ __('Users over effective limit') }}</flux:text>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700 dark:border-green-900/60 dark:bg-green-900/20 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif

        @error('delete')
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-900/20 dark:text-red-300">
                {{ $message }}
            </div>
        @enderror

        @error('bulkQuotaMb')
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-900/20 dark:text-red-300">
                {{ $message }}
            </div>
        @enderror

        @if($users->isEmpty())
            <div class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-12">
                <div class="text-center">
                    <flux:icon name="users" class="mx-auto size-12 text-zinc-400" />
                    <flux:heading size="lg" class="mt-4">{{ __('No users found') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Try changing your search query.') }}</flux:text>
                </div>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-4 py-3 font-medium w-10">
                                <input type="checkbox" wire:model.live="selectAll" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500" />
                            </th>
                            <th class="px-4 py-3 font-medium">{{ __('Username') }}</th>
                            <th class="px-4 py-3 font-medium hidden sm:table-cell">{{ __('Role') }}</th>
                            <th class="px-4 py-3 font-medium hidden md:table-cell">{{ __('Notes') }}</th>
                            <th class="px-4 py-3 font-medium hidden md:table-cell">{{ __('Files') }}</th>
                            <th class="px-4 py-3 font-medium hidden lg:table-cell">{{ __('Used') }}</th>
                            <th class="px-4 py-3 font-medium hidden lg:table-cell">{{ __('Left') }}</th>
                            <th class="px-4 py-3 font-medium hidden md:table-cell">{{ __('Quota') }}</th>
                            <th class="px-4 py-3 font-medium hidden md:table-cell">{{ __('Joined') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($users as $user)
                            @php
                                $usedStorageBytes = (int) ($user->used_storage_bytes ?? 0);
                                $limitBytes = $user->storageLimitBytes();
                                $remainingBytes = max($limitBytes - $usedStorageBytes, 0);
                            @endphp
                            <tr wire:key="user-{{ $user->id }}" class="bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedUserIds"
                                        value="{{ $user->id }}"
                                        class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="user" class="size-5 text-zinc-400" />
                                        <span>{{ $user->username }}</span>
                                        @if(auth()->id() === $user->id)
                                            <flux:badge size="sm" color="sky">{{ __('You') }}</flux:badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 hidden sm:table-cell">
                                    <flux:badge size="sm" :color="$user->role === 'admin' ? 'violet' : 'zinc'">
                                        {{ ucfirst($user->role) }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-zinc-500">
                                    {{ number_format((int) $user->notes_count) }}
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-zinc-500">
                                    {{ number_format((int) $user->files_count) }}
                                </td>
                                <td class="px-4 py-3 hidden lg:table-cell text-zinc-500">
                                    {{ \App\Support\StorageQuota::formatBytes($usedStorageBytes) }}
                                </td>
                                <td class="px-4 py-3 hidden lg:table-cell text-zinc-500">
                                    {{ \App\Support\StorageQuota::formatBytes($remainingBytes) }}
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-zinc-500">
                                    @if($user->storage_quota_bytes)
                                        {{ \App\Support\StorageQuota::formatBytes((int) $user->storage_quota_bytes) }}
                                        <flux:badge size="sm" color="sky" class="ml-1">{{ __('Custom') }}</flux:badge>
                                    @else
                                        {{ __('Default') }}
                                    @endif
                                    <div class="text-xs text-zinc-400 mt-0.5">
                                        {{ __('Limit: :limit', ['limit' => \App\Support\StorageQuota::formatBytes($limitBytes)]) }}
                                    </div>
                                    @if(! $user->storage_quota_bytes)
                                        <div class="text-xs text-zinc-400">
                                            {{ __('Includes grace') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-zinc-500 hidden md:table-cell">{{ $user->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button variant="ghost" size="sm" :href="route('admin.users.edit', $user)" wire:navigate icon="pencil-square" />
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            wire:click="deleteUser({{ $user->id }})"
                                            wire:target="deleteUser({{ $user->id }})"
                                            wire:confirm="Are you sure you want to delete this user?"
                                            icon="trash"
                                            class="text-red-500!"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $users->links() }}
        @endif
    </div>
</div>
