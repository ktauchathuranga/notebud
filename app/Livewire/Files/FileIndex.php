<?php

namespace App\Livewire\Files;

use App\Models\File;
use App\Models\Share;
use App\Support\StorageQuota;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('My Files')]
class FileIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteFile(int $fileId): void
    {
        $file = File::findOrFail($fileId);
        $this->authorize('delete', $file);

        Storage::disk(config('filesystems.uploads'))->delete($file->path);
        $file->delete();
        Cache::tags(['user_'.Auth::id().'_files'])->flush();
    }

    public function render(): View
    {
        $user = Auth::user();
        $usedBytes = StorageQuota::usedBytes($user);
        $limitBytes = StorageQuota::limitBytes($user);

        return view('livewire.files.file-index', [
            'myFiles' => $this->getMyFiles(),
            'sharedFiles' => $this->getSharedFiles(),
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

    private function getMyFiles(): LengthAwarePaginator
    {
        return Auth::user()->files()
            ->when($this->search, function ($query) {
                $query->where('original_name', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(15);
    }

    private function getSharedFiles(): Collection
    {
        $user = Auth::user();

        $sharedFileIds = Cache::tags(['user_'.$user->id.'_files'])->remember(
            'shared_file_ids',
            now()->addHour(),
            fn () => Share::where('shared_with', $user->id)
                ->where('status', 'accepted')
                ->where('shareable_type', File::class)
                ->pluck('shareable_id')
                ->toArray()
        );

        if (empty($sharedFileIds)) {
            return collect();
        }

        return File::whereIn('id', $sharedFileIds)
            ->when($this->search, function ($query) {
                $query->where('original_name', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->get();
    }
}
