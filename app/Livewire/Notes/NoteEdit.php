<?php

namespace App\Livewire\Notes;

use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Edit Note')]
class NoteEdit extends Component
{
    public Note $note;

    public string $title = '';

    public string $content = '';

    public function mount(Note $note): void
    {
        $this->authorize('update', $note);
        $this->note = $note;
        $this->title = $note->title;
        $this->content = $note->content ?? '';
    }

    public function save(): void
    {
        $this->authorize('update', $this->note);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $this->note->update([
            'title' => $this->title,
            'content' => $this->content,
        ]);

        Cache::tags(['user_'.Auth::id().'_notes'])->flush();

        $this->redirect(route('notes.show', $this->note), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.notes.note-edit');
    }
}
