<?php

namespace App\Livewire\Shares;

use App\Models\Share;
use App\Notifications\ShareResponseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Shared with me')]
class IncomingShares extends Component
{
    public function remove(int $shareId): void
    {
        $share = Share::findOrFail($shareId);
        // Only the recipient can remove
        if ($share->shared_with !== Auth::id()) {
            abort(403);
        }
        $share->delete();
    }
    public function accept(int $shareId): void
    {
        $share = Share::findOrFail($shareId);
        $this->authorize('respond', $share);

        $share->update(['status' => 'accepted']);
        $share->sharer->notify(new ShareResponseNotification($share));
    }

    public function reject(int $shareId): void
    {
        $share = Share::findOrFail($shareId);
        $this->authorize('respond', $share);

        $share->update(['status' => 'rejected']);
        $share->sharer->notify(new ShareResponseNotification($share));
    }

    public function render()
    {
        $pendingShares = Share::with(['sharer', 'shareable'])
            ->where('shared_with', Auth::id())
            ->where('status', 'pending')
            ->latest()
            ->get();

        $acceptedShares = Share::with(['sharer', 'shareable'])
            ->where('shared_with', Auth::id())
            ->where('status', 'accepted')
            ->latest()
            ->get();

        return view('livewire.shares.incoming-shares', [
            'pendingShares' => $pendingShares,
            'acceptedShares' => $acceptedShares,
        ]);
    }
}
