<?php

namespace App\Livewire\Notes;

use App\Models\Note;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('View Note')]
class NoteShow extends Component
{
    public Note $note;

    public function mount(Note $note): void
    {
        $this->authorize('view', $note);
        $this->note = $note;
    }

    public function render(): View
    {
        return view('livewire.notes.note-show');
    }
}
