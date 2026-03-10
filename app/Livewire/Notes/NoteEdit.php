<?php

namespace App\Livewire\Notes;

use App\Models\Note;
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

        $this->redirect(route('notes.show', $this->note), navigate: true);
    }

    public function render()
    {
        return view('livewire.notes.note-edit');
    }
}
