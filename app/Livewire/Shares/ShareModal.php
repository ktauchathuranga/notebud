<?php

namespace App\Livewire\Shares;

use App\Models\Share;
use App\Models\User;
use App\Notifications\ShareRequestNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Throwable;

class ShareModal extends Component
{
    public string $shareableType;

    public int $shareableId;

    public string $trigger = 'icon';

    public string $label = 'Share';

    public string $modalName = 'share-modal';

    public string $openEvent = 'open-share-modal';

    public string $username = '';

    public string $message = '';

    public bool $showModal = false;

    public function mount(): void
    {
        $this->modalName = 'share-modal-'.md5($this->shareableType.'-'.$this->shareableId);
        $this->openEvent = 'open-share-modal-'.md5($this->shareableType.'-'.$this->shareableId);
    }

    protected function getListeners(): array
    {
        return [
            $this->openEvent => 'open',
        ];
    }

    public function useRecentUsername(string $username): void
    {
        $currentUsernames = collect(explode(',', $this->username))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->values();

        if (! $currentUsernames->contains($username)) {
            $currentUsernames->push($username);
        }

        $this->username = $currentUsernames->implode(', ');
        $this->resetValidation('username');
    }

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
            'username' => ['required', 'string'],
            'message' => ['nullable', 'string', 'max:255'],
        ]);

        $usernames = collect(explode(',', $this->username))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->unique()
            ->values();

        if ($usernames->isEmpty()) {
            $this->addError('username', 'Enter at least one valid username.');
            $this->dispatch('share-feedback', type: 'error', message: 'Enter at least one valid username.');

            return;
        }

        $recipients = User::query()
            ->whereIn('username', $usernames)
            ->get(['id', 'username']);

        $missingUsernames = $usernames->diff($recipients->pluck('username'))->values();

        if ($missingUsernames->isNotEmpty()) {
            $this->addError('username', 'Usernames not found: '.$missingUsernames->implode(', ').'.');
            $this->dispatch('share-feedback', type: 'error', message: 'Some usernames were not found.');

            return;
        }

        if ($recipients->contains('id', Auth::id())) {
            $this->addError('username', 'You cannot share with yourself.');
            $this->dispatch('share-feedback', type: 'error', message: 'You cannot share with yourself.');

            return;
        }

        $existingRecipientIds = Share::query()
            ->where('shared_by', Auth::id())
            ->whereIn('shared_with', $recipients->pluck('id'))
            ->where('shareable_type', $this->shareableType)
            ->where('shareable_id', $this->shareableId)
            ->whereIn('status', ['pending', 'accepted'])
            ->pluck('shared_with');

        if ($existingRecipientIds->isNotEmpty()) {
            $alreadySharedUsernames = $recipients
                ->whereIn('id', $existingRecipientIds)
                ->pluck('username')
                ->values();

            $this->addError('username', 'Already shared with: '.$alreadySharedUsernames->implode(', ').'.');
            $this->dispatch('share-feedback', type: 'error', message: 'Already shared with one or more selected users.');

            return;
        }

        try {
            foreach ($recipients as $recipient) {
                $share = Share::create([
                    'shared_by' => Auth::id(),
                    'shared_with' => $recipient->id,
                    'shareable_type' => $this->shareableType,
                    'shareable_id' => $this->shareableId,
                    'status' => 'pending',
                    'message' => $this->message ?: null,
                ]);

                $recipient->notify(new ShareRequestNotification($share));
                Cache::tags(['user_'.$recipient->id.'_shares'])->flush();
                Cache::tags(['user_'.Auth::id().'_shares'])->flush();
            }

            $recipientCount = $recipients->count();
            $successMessage = $recipientCount === 1
                ? 'Share request sent successfully.'
                : "Share requests sent to {$recipientCount} users.";

            $this->close();
            $this->dispatch('share-feedback', type: 'success', message: $successMessage);
            $this->dispatch('shared');
        } catch (Throwable $e) {
            report($e);
            $this->addError('username', 'Failed to send share request. Please try again.');
            $this->dispatch('share-feedback', type: 'error', message: 'Failed to send share request. Please try again.');
        }
    }

    public function render()
    {
        $recentUsernames = collect(Cache::tags(['user_'.Auth::id().'_shares'])->remember(
            'recent_usernames',
            now()->addHour(),
            function () {
                return Share::query()
                    ->where('shared_by', Auth::id())
                    ->with('recipient:id,username')
                    ->latest()
                    ->get()
                    ->pluck('recipient.username')
                    ->filter()
                    ->unique()
                    ->take(6)
                    ->values()
                    ->toArray();
            }
        ));

        return view('livewire.shares.share-modal', [
            'recentUsernames' => $recentUsernames,
        ]);
    }
}
