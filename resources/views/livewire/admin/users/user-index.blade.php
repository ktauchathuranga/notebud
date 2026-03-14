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
                            <th class="px-4 py-3 font-medium">{{ __('Username') }}</th>
                            <th class="px-4 py-3 font-medium hidden sm:table-cell">{{ __('Role') }}</th>
                            <th class="px-4 py-3 font-medium hidden md:table-cell">{{ __('Joined') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($users as $user)
                            <tr class="bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
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
                                <td class="px-4 py-3 text-zinc-500 hidden md:table-cell">{{ $user->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button variant="ghost" size="sm" :href="route('admin.users.edit', $user)" wire:navigate icon="pencil-square" />
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            wire:click="deleteUser({{ $user->id }})"
                                            wire:confirm="Are you sure you want to delete this user?"
                                            icon="trash"
                                            class="!text-red-500"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
