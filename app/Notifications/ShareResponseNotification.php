<?php

namespace App\Notifications;

use App\Models\Share;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShareResponseNotification extends Notification
{
    use Queueable;

    public function __construct(public Share $share) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $type = $this->share->shareable_type === 'App\\Models\\Note' ? 'note' : 'file';
        $name = $type === 'note'
            ? $this->share->shareable->title
            : $this->share->shareable->original_name;

        return [
            'share_id' => $this->share->id,
            'responded_by' => $this->share->recipient->username,
            'type' => $type,
            'name' => $name,
            'status' => $this->share->status,
        ];
    }
}
