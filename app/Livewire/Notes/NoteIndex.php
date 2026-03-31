<?php

namespace App\Livewire\Notes;

use App\Models\Note;
use App\Models\Share;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('My Notes')]
class NoteIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteNote(int $noteId): void
    {
        $note = Note::findOrFail($noteId);
        $this->authorize('delete', $note);
        $note->delete();

        Cache::tags(['user_'.Auth::id().'_notes'])->flush();
    }

    public function render(): View
    {
        return view('livewire.notes.note-index', [
            'myNotes' => $this->getMyNotes(),
            'sharedNotes' => $this->getSharedNotes(),
        ]);
    }

    private function getMyNotes(): LengthAwarePaginator
    {
        return Auth::user()->notes()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('content', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(12);
    }

    private function getSharedNotes(): Collection
    {
        $user = Auth::user();

        $sharedNoteIds = Cache::tags(['user_'.$user->id.'_notes'])->remember(
            'shared_note_ids',
            now()->addHour(),
            fn () => Share::where('shared_with', $user->id)
                ->where('status', 'accepted')
                ->where('shareable_type', Note::class)
                ->pluck('shareable_id')
                ->toArray()
        );

        if (empty($sharedNoteIds)) {
            return collect();
        }

        return Note::whereIn('id', $sharedNoteIds)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('content', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->get();
    }
}
