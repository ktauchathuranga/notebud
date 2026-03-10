<?php

namespace App\Livewire\Files;

use App\Models\File;
use App\Models\Share;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('My Files')]
class FileIndex extends Component
{
    #[Url]
    public string $search = '';

    public function deleteFile(int $fileId): void
    {
        $file = File::findOrFail($fileId);
        $this->authorize('delete', $file);

        Storage::disk('uploads')->delete($file->path);
        $file->delete();
    }

    public function render()
    {
        $user = Auth::user();

        $myFiles = $user->files()
            ->when($this->search, function ($query) {
                $query->where('original_name', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->get();

        $sharedFileIds = Share::where('shared_with', $user->id)
            ->where('status', 'accepted')
            ->where('shareable_type', File::class)
            ->pluck('shareable_id');

        $sharedFiles = File::whereIn('id', $sharedFileIds)
            ->when($this->search, function ($query) {
                $query->where('original_name', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->get();

        return view('livewire.files.file-index', [
            'myFiles' => $myFiles,
            'sharedFiles' => $sharedFiles,
        ]);
    }
}
