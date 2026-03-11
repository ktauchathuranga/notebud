<?php

namespace App\Livewire\Notes;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Create Note')]
class NoteCreate extends Component
{
    public string $title = '';

    public string $content = '';

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        Auth::user()->notes()->create([
            'title' => $this->title,
            'content' => $this->content,
        ]);

        $this->redirect(route('notes.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.notes.note-create');
    }
}
