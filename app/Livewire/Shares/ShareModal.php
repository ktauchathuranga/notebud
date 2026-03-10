<?php

namespace App\Livewire\Shares;

use App\Models\Share;
use App\Models\User;
use App\Notifications\ShareRequestNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ShareModal extends Component
{
    public string $shareableType;
    public int $shareableId;
    public string $username = '';
    public string $message = '';
    public bool $showModal = false;

    public function open(): void
    {
        $this->showModal = true;
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->reset('username', 'message');
        $this->resetValidation();
    }

    public function share(): void
    {
        $this->validate([
            'username' => ['required', 'string', 'exists:users,username'],
            'message' => ['nullable', 'string', 'max:255'],
        ]);

        $recipient = User::where('username', $this->username)->first();

        if ($recipient->id === Auth::id()) {
            $this->addError('username', 'You cannot share with yourself.');
            return;
        }

        // Check for existing pending/accepted share
        $existing = Share::where('shared_by', Auth::id())
            ->where('shared_with', $recipient->id)
            ->where('shareable_type', $this->shareableType)
            ->where('shareable_id', $this->shareableId)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($existing) {
            $this->addError('username', 'Already shared with this user.');
            return;
        }

        $share = Share::create([
            'shared_by' => Auth::id(),
            'shared_with' => $recipient->id,
            'shareable_type' => $this->shareableType,
            'shareable_id' => $this->shareableId,
            'status' => 'pending',
            'message' => $this->message ?: null,
        ]);

        $recipient->notify(new ShareRequestNotification($share));

        $this->close();
        $this->dispatch('shared');
    }

    public function render()
    {
        return view('livewire.shares.share-modal');
    }
}
