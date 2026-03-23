<?php

namespace App\Livewire\Notes;

use App\Models\Note;
use App\Models\Share;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

        Cache::tags(['user_'.Auth::id().'_notes'])->flush();
    }

    public function render()
    {
        $user = Auth::user();

        $search = $this->search;

        $myNotes = Cache::tags(['user_'.$user->id.'_notes'])->remember(
            'my_notes_'.md5($search),
            now()->addHour(),
            function () use ($user, $search) {
                return $user->notes()
                    ->when($search, function ($query) use ($search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('title', 'like', '%'.$search.'%')
                                ->orWhere('content', 'like', '%'.$search.'%');
                        });
                    })
                    ->latest()
                    ->get();
            }
        );

        $sharedNoteIds = Cache::tags(['user_'.$user->id.'_notes'])->remember(
            'shared_note_ids',
            now()->addHour(),
            function () use ($user) {
                return Share::where('shared_with', $user->id)
                    ->where('status', 'accepted')
                    ->where('shareable_type', Note::class)
                    ->pluck('shareable_id');
            }
        );

        $sharedNotes = Cache::tags(['user_'.$user->id.'_notes'])->remember(
            'shared_notes_'.md5($search),
            now()->addHour(),
            function () use ($sharedNoteIds, $search) {
                return Note::whereIn('id', $sharedNoteIds)
                    ->when($search, function ($query) use ($search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('title', 'like', '%'.$search.'%')
                                ->orWhere('content', 'like', '%'.$search.'%');
                        });
                    })
                    ->latest()
                    ->get();
            }
        );

        return view('livewire.notes.note-index', [
            'myNotes' => $myNotes,
            'sharedNotes' => $sharedNotes,
        ]);
    }
}
