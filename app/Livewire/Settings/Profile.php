<?php

namespace App\Livewire\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Profile settings')]
class Profile extends Component
{
    use ProfileValidationRules;
    use WithFileUploads;

    public string $username = '';

    public ?TemporaryUploadedFile $avatar = null;

    public function mount(): void
    {
        $this->username = Auth::user()->username;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate(array_merge(
            $this->profileRules($user->id),
            [
                'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ],
        ));

        $user->username = $validated['username'];

        if ($this->avatar) {
            if ($user->avatar_path) {
                Storage::disk($user->avatarDisk())->delete($user->avatar_path);
            }

            $directory = (string) config('filesystems.avatars_path', 'avatars');
            $user->avatar_path = $this->avatar->store($directory, $user->avatarDisk());
        }

        $user->save();

        $this->reset('avatar');

        $this->dispatch('profile-updated', name: $user->username);
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk($user->avatarDisk())->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        $this->dispatch('profile-updated', name: $user->username);
    }
}
