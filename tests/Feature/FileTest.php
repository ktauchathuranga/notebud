<?php

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('files index requires authentication', function () {
    $this->get('/files')->assertRedirect('/login');
});

test('files index page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/files')
        ->assertOk();
});

test('user can upload a file', function () {
    Storage::fake('uploads');

    $user = User::factory()->create();

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('document.pdf', 1024);

    Livewire\Livewire::test(App\Livewire\Files\FileUpload::class)
        ->set('file', $file)
        ->call('save');

    expect($user->files()->count())->toBe(1);
    expect($user->files()->first()->original_name)->toBe('document.pdf');
});

test('file upload rejects files over 10MB', function () {
    Storage::fake('uploads');

    $user = User::factory()->create();

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('large.pdf', 11000); // 11MB

    Livewire\Livewire::test(App\Livewire\Files\FileUpload::class)
        ->set('file', $file)
        ->call('save')
        ->assertHasErrors('file');
});

test('user can download own file', function () {
    Storage::fake('uploads');

    $user = User::factory()->create();
    $uploadedFile = UploadedFile::fake()->create('test.txt', 100);
    $storedName = 'test-stored.txt';
    Storage::disk('uploads')->putFileAs('', $uploadedFile, $storedName);

    $file = File::create([
        'user_id' => $user->id,
        'original_name' => 'test.txt',
        'stored_name' => $storedName,
        'path' => $storedName,
        'size' => 100 * 1024,
        'mime_type' => 'text/plain',
    ]);

    $this->actingAs($user)
        ->get("/files/{$file->id}/download")
        ->assertOk();
});

test('user cannot download another users file', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $file = File::create([
        'user_id' => $other->id,
        'original_name' => 'secret.pdf',
        'stored_name' => 'stored.pdf',
        'path' => 'stored.pdf',
        'size' => 1024,
        'mime_type' => 'application/pdf',
    ]);

    $this->actingAs($user)
        ->get("/files/{$file->id}/download")
        ->assertForbidden();
});

test('user can delete own file', function () {
    Storage::fake('uploads');

    $user = User::factory()->create();
    $storedName = 'delete-me.txt';
    Storage::disk('uploads')->put($storedName, 'content');

    $file = File::create([
        'user_id' => $user->id,
        'original_name' => 'delete-me.txt',
        'stored_name' => $storedName,
        'path' => $storedName,
        'size' => 7,
        'mime_type' => 'text/plain',
    ]);

    $this->actingAs($user);

    Livewire\Livewire::test(App\Livewire\Files\FileIndex::class)
        ->call('deleteFile', $file->id);

    expect(File::find($file->id))->toBeNull();
    Storage::disk('uploads')->assertMissing($storedName);
});
