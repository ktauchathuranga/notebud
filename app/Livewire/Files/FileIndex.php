<?php

namespace App\Livewire\Files;

use App\Models\File;
use App\Models\Share;
use App\Support\StorageQuota;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('My Files')]
class FileIndex extends Component
{
    #[Url]
    public string $search = '';

    public function deleteFile(int $fileId): void
    {
        $file = File::findOrFail($fileId);
        $this->authorize('delete', $file);

        Storage::disk(config('filesystems.uploads'))->delete($file->path);
        $file->delete();
    }

    public function render()
    {
        $user = Auth::user();

        $myFiles = $user->files()
            ->when($this->search, function ($query) {
                $query->where('original_name', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->get();

        $sharedFileIds = Share::where('shared_with', $user->id)
            ->where('status', 'accepted')
            ->where('shareable_type', File::class)
            ->pluck('shareable_id');

        $sharedFiles = File::whereIn('id', $sharedFileIds)
            ->when($this->search, function ($query) {
                $query->where('original_name', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->get();

        $usedBytes = StorageQuota::usedBytes($user);
        $limitBytes = StorageQuota::limitBytes($user);

        return view('livewire.files.file-index', [
            'myFiles' => $myFiles,
            'sharedFiles' => $sharedFiles,
            'usedStorageBytes' => $usedBytes,
            'storageLimitBytes' => $limitBytes,
            'remainingStorageBytes' => max($limitBytes - $usedBytes, 0),
            'storageUsagePercent' => $limitBytes > 0
                ? min(100, (int) round(($usedBytes / $limitBytes) * 100))
                : 0,
            'storageUsageText' => sprintf(
                '%s / %s',
                StorageQuota::formatBytes($usedBytes),
                StorageQuota::formatBytes($limitBytes),
            ),
        ]);
    }
}
