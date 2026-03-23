<?php

namespace App\Livewire\Files;

use App\Models\File;
use App\Models\User;
use App\Support\StorageQuota;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

        $user = Auth::user();
        $incomingBytes = (int) $this->file->getSize();
        $mimeType = (string) $this->file->getMimeType();

        $originalName = $this->file->getClientOriginalName();
        $storedName = Str::uuid().'.'.$this->file->getClientOriginalExtension();
        $directory = (string) config('filesystems.uploads_path', 'files');
        $diskName = (string) config('filesystems.uploads');
        $path = $this->file->storeAs($directory, $storedName, $diskName);

        try {
            DB::transaction(function () use ($user, $incomingBytes, $originalName, $storedName, $path, $mimeType): void {
                $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $usedBytes = (int) File::query()->where('user_id', $lockedUser->id)->sum('size');
                $limitBytes = StorageQuota::limitBytes($lockedUser);

                if (($usedBytes + $incomingBytes) > $limitBytes) {
                    $requiredFreeBytes = ($usedBytes + $incomingBytes) - $limitBytes;

                    throw ValidationException::withMessages([
                        'file' => sprintf(
                            'Storage limit reached. Used %s of %s. Free up %s to upload this file.',
                            StorageQuota::formatBytes($usedBytes),
                            StorageQuota::formatBytes($limitBytes),
                            StorageQuota::formatBytes($requiredFreeBytes),
                        ),
                    ]);
                }

                $lockedUser->files()->create([
                    'original_name' => $originalName,
                    'stored_name' => $storedName,
                    'path' => $path,
                    'size' => $incomingBytes,
                    'mime_type' => $mimeType,
                ]);
            });
            Cache::tags(['user_'.$user->id.'_files'])->flush();
        } catch (ValidationException $exception) {
            Storage::disk($diskName)->delete($path);

            throw $exception;
        } catch (\Throwable $exception) {
            Storage::disk($diskName)->delete($path);

            throw $exception;
        }

        $this->redirect(route('files.index'), navigate: true);
    }

    public function render()
    {
        $user = Auth::user();
        $usedBytes = StorageQuota::usedBytes($user);
        $limitBytes = StorageQuota::limitBytes($user);

        return view('livewire.files.file-upload', [
            'usedStorageText' => StorageQuota::formatBytes($usedBytes),
            'limitStorageText' => StorageQuota::formatBytes($limitBytes),
            'remainingStorageText' => StorageQuota::formatBytes(max($limitBytes - $usedBytes, 0)),
        ]);
    }
}
