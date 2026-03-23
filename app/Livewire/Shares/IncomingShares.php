<?php

namespace App\Livewire\Shares;

use App\Models\Share;
use App\Notifications\ShareResponseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        $this->flushCaches();
    }

    public function accept(int $shareId): void
    {
        $share = Share::findOrFail($shareId);
        $this->authorize('respond', $share);

        $share->update(['status' => 'accepted']);
        $share->sharer->notify(new ShareResponseNotification($share));
        $this->flushCaches();
    }

    public function reject(int $shareId): void
    {
        $share = Share::findOrFail($shareId);
        $this->authorize('respond', $share);

        $share->update(['status' => 'rejected']);
        $share->sharer->notify(new ShareResponseNotification($share));
        $this->flushCaches();
    }

    private function flushCaches(): void
    {
        Cache::tags(['user_'.Auth::id().'_shares'])->flush();
        Cache::tags(['user_'.Auth::id().'_notes'])->flush();
        Cache::tags(['user_'.Auth::id().'_files'])->flush();
    }

    public function render()
    {
        $pendingShares = Cache::tags(['user_'.Auth::id().'_shares'])->remember(
            'pending_shares',
            now()->addHour(),
            function () {
                return Share::with(['sharer', 'shareable'])
                    ->where('shared_with', Auth::id())
                    ->where('status', 'pending')
                    ->latest()
                    ->get();
            }
        );

        $acceptedShares = Cache::tags(['user_'.Auth::id().'_shares'])->remember(
            'accepted_shares',
            now()->addHour(),
            function () {
                return Share::with(['sharer', 'shareable'])
                    ->where('shared_with', Auth::id())
                    ->where('status', 'accepted')
                    ->latest()
                    ->get();
            }
        );

        return view('livewire.shares.incoming-shares', [
            'pendingShares' => $pendingShares,
            'acceptedShares' => $acceptedShares,
        ]);
    }
}
