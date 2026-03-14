<?php

namespace App\Livewire\Files;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Upload File')]
class FileUpload extends Component
{
    use WithFileUploads;

    public $file;

    public function save(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB
        ]);

        $originalName = $this->file->getClientOriginalName();
        $storedName = Str::uuid().'.'.$this->file->getClientOriginalExtension();
        $path = $this->file->storeAs('', $storedName, config('filesystems.uploads'));

        Auth::user()->files()->create([
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'path' => $path,
            'size' => $this->file->getSize(),
            'mime_type' => $this->file->getMimeType(),
        ]);

        $this->redirect(route('files.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.files.file-upload');
    }
}
