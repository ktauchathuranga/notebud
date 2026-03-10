<?php

use App\Models\Note;
use App\Models\Share;
use App\Models\User;
use App\Notifications\ShareRequestNotification;
use App\Notifications\ShareResponseNotification;
use Illuminate\Support\Facades\Notification;

test('user can share a note with another user', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner);

    Livewire\Livewire::test(App\Livewire\Shares\ShareModal::class, [
        'shareableType' => Note::class,
        'shareableId' => $note->id,
    ])
        ->set('username', $recipient->username)
        ->set('message', 'Check this out!')
        ->call('share');

    expect(Share::count())->toBe(1);
    expect(Share::first()->status)->toBe('pending');

    Notification::assertSentTo($recipient, ShareRequestNotification::class);
});

test('user cannot share with themselves', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire\Livewire::test(App\Livewire\Shares\ShareModal::class, [
        'shareableType' => Note::class,
        'shareableId' => $note->id,
    ])
        ->set('username', $user->username)
        ->call('share')
        ->assertHasErrors('username');
});

test('recipient can accept a share', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    $share = Share::create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
        'status' => 'pending',
    ]);

    $this->actingAs($recipient);

    Livewire\Livewire::test(App\Livewire\Shares\IncomingShares::class)
        ->call('accept', $share->id);

    $share->refresh();
    expect($share->status)->toBe('accepted');

    Notification::assertSentTo($owner, ShareResponseNotification::class);
});

test('recipient can reject a share', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    $share = Share::create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
        'status' => 'pending',
    ]);

    $this->actingAs($recipient);

    Livewire\Livewire::test(App\Livewire\Shares\IncomingShares::class)
        ->call('reject', $share->id);

    $share->refresh();
    expect($share->status)->toBe('rejected');
});

test('accepted share allows note viewing', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    Share::create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
        'status' => 'accepted',
    ]);

    $this->actingAs($recipient)
        ->get("/notes/{$note->id}")
        ->assertOk();
});

test('pending share does not allow note viewing', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    Share::create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
        'status' => 'pending',
    ]);

    $this->actingAs($recipient)
        ->get("/notes/{$note->id}")
        ->assertForbidden();
});

test('non-recipient cannot accept a share', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    $share = Share::create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
        'status' => 'pending',
    ]);

    $this->actingAs($intruder);

    Livewire\Livewire::test(App\Livewire\Shares\IncomingShares::class)
        ->call('accept', $share->id)
        ->assertForbidden();
});
