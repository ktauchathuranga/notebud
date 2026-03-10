<?php

namespace App\Livewire\Notes;

use App\Models\Note;
use App\Models\Share;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('My Notes')]
class NoteIndex extends Component
{
    #[Url]
    public string $search = '';

    public function deleteNote(int $noteId): void
    {
        $note = Note::findOrFail($noteId);
        $this->authorize('delete', $note);
        $note->delete();
    }

    public function render()
    {
        $user = Auth::user();

        $myNotes = $user->notes()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('content', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->get();

        $sharedNoteIds = Share::where('shared_with', $user->id)
            ->where('status', 'accepted')
            ->where('shareable_type', Note::class)
            ->pluck('shareable_id');

        $sharedNotes = Note::whereIn('id', $sharedNoteIds)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('content', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->get();

        return view('livewire.notes.note-index', [
            'myNotes' => $myNotes,
            'sharedNotes' => $sharedNotes,
        ]);
    }
}
