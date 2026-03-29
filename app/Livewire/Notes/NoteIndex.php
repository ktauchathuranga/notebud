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

        // Cache only note IDs, then re-query for Eloquent models
        $myNoteIds = Cache::tags(['user_'.$user->id.'_notes'])->remember(
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
                    ->pluck('id')
                    ->toArray();
            }
        );
        if (empty($myNoteIds)) {
            $myNotes = collect();
        } else {
            $myNotes = Note::whereIn('id', $myNoteIds)
                ->get()
                ->sortBy(fn($model) => array_search($model->id, $myNoteIds))
                ->values();
        }

        $sharedNoteIds = Cache::tags(['user_'.$user->id.'_notes'])->remember(
            'shared_note_ids',
            now()->addHour(),
            function () use ($user) {
                return Share::where('shared_with', $user->id)
                    ->where('status', 'accepted')
                    ->where('shareable_type', Note::class)
                    ->pluck('shareable_id')
                    ->toArray();
            }
        );

        if (empty($sharedNoteIds)) {
            $sharedNotes = collect();
        } else {
            $sharedNotes = Note::whereIn('id', $sharedNoteIds)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', '%'.$search.'%')
                            ->orWhere('content', 'like', '%'.$search.'%');
                    });
                })
                ->latest()
                ->get()
                ->sortBy(fn($model) => array_search($model->id, $sharedNoteIds))
                ->values();
        }

        return view('livewire.notes.note-index', [
            'myNotes' => $myNotes,
            'sharedNotes' => $sharedNotes,
        ]);
    }
}
