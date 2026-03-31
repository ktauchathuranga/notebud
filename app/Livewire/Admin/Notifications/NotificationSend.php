<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\User;
use App\Notifications\AdminNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Admin Notifications')]
class NotificationSend extends Component
{
    public string $title = '';

    public string $message = '';

    public string $priority = 'info';

    public string $target = 'all';

    public string $action_url = '';

    #[Url]
    public string $userSearch = '';

    /**
     * @var array<int>
     */
    public array $selectedUserIds = [];

    public function send(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
            'priority' => ['required', 'in:info,success,warning,danger'],
            'target' => ['required', 'in:all,selected'],
            'action_url' => ['nullable', 'url:http,https', 'max:255'],
            'selectedUserIds' => ['array'],
            'selectedUserIds.*' => ['integer', 'exists:users,id'],
        ]);

        if ($validated['target'] === 'selected' && empty($validated['selectedUserIds'])) {
            $this->addError('selectedUserIds', __('Select at least one user for selected delivery.'));

            return;
        }

        $recipients = User::query()
            ->when(
                $validated['target'] === 'selected',
                fn ($query) => $query->whereIn('id', $validated['selectedUserIds'])
            )
            ->get();

        Notification::send(
            $recipients,
            new AdminNotification(
                title: $validated['title'],
                message: $validated['message'],
                priority: $validated['priority'],
                actionUrl: $validated['action_url'] ?: null,
                sentBy: Auth::user()->username,
            )
        );

        $recipientCount = $recipients->count();

        $this->reset('title', 'message', 'action_url', 'selectedUserIds');
        $this->priority = 'info';
        $this->target = 'all';

        session()->flash('status', "Notification sent to {$recipientCount} user(s).");
    }

    public function render(): View
    {
        $users = User::query()
            ->when($this->userSearch, fn ($query) => $query->where('username', 'like', '%'.$this->userSearch.'%'))
            ->orderBy('username')
            ->get();

        return view('livewire.admin.notifications.notification-send', [
            'users' => $users,
        ]);
    }
}
