<?php

namespace App\Livewire\Files;

use App\Models\File;
use App\Models\Share;
use App\Support\StorageQuota;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        Cache::tags(['user_'.Auth::id().'_files'])->flush();
    }

    public function render()
    {
        $user = Auth::user();

        $search = $this->search;

        // Cache only file IDs, then re-query for Eloquent models
        $myFileIds = Cache::tags(['user_'.$user->id.'_files'])->remember(
            'my_files_'.md5($search),
            now()->addHour(),
            function () use ($user, $search) {
                return $user->files()
                    ->when($search, function ($query) use ($search) {
                        $query->where('original_name', 'like', '%'.$search.'%');
                    })
                    ->latest()
                    ->pluck('id')
                    ->toArray();
            }
        );
        if (empty($myFileIds)) {
            $myFiles = collect();
        } else {
            $myFiles = File::whereIn('id', $myFileIds)
                ->get()
                ->sortBy(fn($model) => array_search($model->id, $myFileIds))
                ->values();
        }

        $sharedFileIds = Cache::tags(['user_'.$user->id.'_files'])->remember(
            'shared_file_ids',
            now()->addHour(),
            function () use ($user) {
                return Share::where('shared_with', $user->id)
                    ->where('status', 'accepted')
                    ->where('shareable_type', File::class)
                    ->pluck('shareable_id')
                    ->toArray();
            }
        );

        if (empty($sharedFileIds)) {
            $sharedFiles = collect();
        } else {
            $sharedFiles = File::whereIn('id', $sharedFileIds)
                ->when($search, function ($query) use ($search) {
                    $query->where('original_name', 'like', '%'.$search.'%');
                })
                ->latest()
                ->get()
                ->sortBy(fn($model) => array_search($model->id, $sharedFileIds))
                ->values();
        }

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
