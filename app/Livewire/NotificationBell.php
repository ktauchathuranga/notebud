<?php

namespace App\Livewire;

use App\Models\File;
use App\Models\Note;
use App\Models\Share;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class NotificationBell extends Component
{
    public function openNotification(string $notificationId): void
    {
        $notification = Auth::user()->notifications()->where('id', $notificationId)->first();

        if (! $notification) {
            return;
        }

        $notification->markAsRead();

        if (! isset($notification->data['shared_by'])) {
            return;
        }

        $share = Share::query()
            ->whereKey((int) ($notification->data['share_id'] ?? 0))
            ->where('shared_with', Auth::id())
            ->first();

        if (! $share) {
            return;
        }

        if ($share->status !== 'accepted') {
            $this->redirectRoute('shares.incoming', navigate: true);

            return;
        }

        if ($share->shareable_type === Note::class) {
            $this->redirectRoute('notes.show', ['note' => $share->shareable_id], navigate: true);

            return;
        }

        if ($share->shareable_type === File::class) {
            $this->redirectRoute('files.download', ['file' => $share->shareable_id]);
        }
    }

    public function markAsRead(string $notificationId): void
    {
        Auth::user()->notifications()->where('id', $notificationId)->first()?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function clearAll(): void
    {
        Auth::user()->notifications()->delete();
    }

    public function render(): View
    {
        return view('livewire.notification-bell', [
            'notifications' => Auth::user()->notifications()->latest()->take(20)->get(),
            'unreadCount' => Auth::user()->unreadNotifications()->count(),
        ]);
    }
}
