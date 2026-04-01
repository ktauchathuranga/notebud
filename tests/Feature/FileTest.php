<?php

use App\Livewire\Files\FileIndex;
use App\Livewire\Files\FileUpload;
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

    Livewire\Livewire::test(FileUpload::class)
        ->set('file', $file)
        ->call('save');

    expect($user->files()->count())->toBe(1);
    expect($user->files()->first()->original_name)->toBe('document.pdf');
    expect($user->files()->first()->path)->toStartWith('files/');
});

test('upload is rejected when owned storage quota would be exceeded', function () {
    Storage::fake('uploads');

    config()->set('filesystems.storage_quota.default_bytes', 1024 * 1024); // 1MB
    config()->set('filesystems.storage_quota.grace_bytes', 0);

    $user = User::factory()->create();
    $other = User::factory()->create();

    File::factory()->create([
        'user_id' => $user->id,
        'size' => 900 * 1024,
    ]);

    // Other users' files must not count toward this user's quota.
    File::factory()->create([
        'user_id' => $other->id,
        'size' => 10 * 1024 * 1024,
    ]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('over-limit.pdf', 200); // 200KB

    Livewire\Livewire::test(FileUpload::class)
        ->set('file', $file)
        ->call('save')
        ->assertHasErrors('file');
});

test('grace bytes allow slightly above quota uploads', function () {
    Storage::fake('uploads');

    config()->set('filesystems.storage_quota.default_bytes', 1024 * 1024); // 1MB
    config()->set('filesystems.storage_quota.grace_bytes', 128 * 1024); // 128KB grace

    $user = User::factory()->create();

    File::factory()->create([
        'user_id' => $user->id,
        'size' => 980 * 1024,
    ]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('within-grace.pdf', 100); // 100KB

    Livewire\Livewire::test(FileUpload::class)
        ->set('file', $file)
        ->call('save')
        ->assertHasNoErrors('file');

    expect($user->fresh()->files()->count())->toBe(2);
});

test('file upload rejects files over 10MB', function () {
    Storage::fake('uploads');

    $user = User::factory()->create();

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('large.pdf', 11000); // 11MB

    Livewire\Livewire::test(FileUpload::class)
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

    $file = File::factory()->create([
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

    $file = File::factory()->create([
        'user_id' => $other->id,
        'original_name' => 'secret.pdf',
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

    $file = File::factory()->create([
        'user_id' => $user->id,
        'original_name' => 'delete-me.txt',
        'stored_name' => $storedName,
        'path' => $storedName,
    ]);

    $this->actingAs($user);

    Livewire\Livewire::test(FileIndex::class)
        ->call('deleteFile', $file->id);

    expect(File::find($file->id))->toBeNull();
    Storage::disk('uploads')->assertMissing($storedName);
});

test('download returns 404 when file is missing from disk', function () {
    Storage::fake('uploads');

    $user = User::factory()->create();

    $file = File::factory()->create([
        'user_id' => $user->id,
        'original_name' => 'missing.pdf',
        'path' => 'files/missing-stored.pdf',
    ]);

    $this->actingAs($user)
        ->get("/files/{$file->id}/download")
        ->assertNotFound();
});
