<?php

use App\Livewire\Shares\IncomingShares;
use App\Livewire\Shares\ShareModal;
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

    Livewire\Livewire::test(ShareModal::class, [
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

    Livewire\Livewire::test(ShareModal::class, [
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

    $share = Share::factory()->create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
    ]);

    $this->actingAs($recipient);

    Livewire\Livewire::test(IncomingShares::class)
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

    $share = Share::factory()->create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
    ]);

    $this->actingAs($recipient);

    Livewire\Livewire::test(IncomingShares::class)
        ->call('reject', $share->id);

    $share->refresh();
    expect($share->status)->toBe('rejected');
});

test('accepted share allows note viewing', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    Share::factory()->accepted()->create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
    ]);

    $this->actingAs($recipient)
        ->get("/notes/{$note->id}")
        ->assertOk();
});

test('pending share does not allow note viewing', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    Share::factory()->create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
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

    $share = Share::factory()->create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
    ]);

    $this->actingAs($intruder);

    Livewire\Livewire::test(IncomingShares::class)
        ->call('accept', $share->id)
        ->assertForbidden();
});

test('share modal shows recently shared usernames and can autofill', function () {
    $owner = User::factory()->create();
    $firstRecipient = User::factory()->create();
    $secondRecipient = User::factory()->create();

    $firstNote = Note::factory()->create(['user_id' => $owner->id]);
    $secondNote = Note::factory()->create(['user_id' => $owner->id]);

    Share::factory()->accepted()->create([
        'shared_by' => $owner->id,
        'shared_with' => $firstRecipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $firstNote->id,
    ]);

    Share::factory()->create([
        'shared_by' => $owner->id,
        'shared_with' => $secondRecipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $secondNote->id,
    ]);

    $this->actingAs($owner);

    Livewire\Livewire::test(ShareModal::class, [
        'shareableType' => Note::class,
        'shareableId' => $firstNote->id,
    ])
        ->assertSee($firstRecipient->username)
        ->assertSee($secondRecipient->username)
        ->call('useRecentUsername', $secondRecipient->username)
        ->assertSet('username', $secondRecipient->username);
});

test('user can share a note with multiple users using comma separated usernames', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $recipientA = User::factory()->create();
    $recipientB = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner);

    Livewire\Livewire::test(ShareModal::class, [
        'shareableType' => Note::class,
        'shareableId' => $note->id,
    ])
        ->set('username', $recipientA->username.', '.$recipientB->username)
        ->set('message', 'Please review this note.')
        ->call('share');

    expect(
        Share::query()
            ->where('shared_by', $owner->id)
            ->where('shareable_type', Note::class)
            ->where('shareable_id', $note->id)
            ->count()
    )->toBe(2);

    Notification::assertSentTo($recipientA, ShareRequestNotification::class);
    Notification::assertSentTo($recipientB, ShareRequestNotification::class);
});

test('recent username click appends to existing comma separated input', function () {
    $owner = User::factory()->create();
    $recipientA = User::factory()->create();
    $recipientB = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    Share::factory()->accepted()->create([
        'shared_by' => $owner->id,
        'shared_with' => $recipientA->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
    ]);

    Share::factory()->accepted()->create([
        'shared_by' => $owner->id,
        'shared_with' => $recipientB->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
    ]);

    $this->actingAs($owner);

    Livewire\Livewire::test(ShareModal::class, [
        'shareableType' => Note::class,
        'shareableId' => $note->id,
    ])
        ->set('username', $recipientA->username)
        ->call('useRecentUsername', $recipientB->username)
        ->assertSet('username', $recipientA->username.', '.$recipientB->username);
});

test('recipient can remove an accepted share', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    $share = Share::factory()->accepted()->create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
    ]);

    $this->actingAs($recipient);

    Livewire\Livewire::test(IncomingShares::class)
        ->call('remove', $share->id);

    expect(Share::find($share->id))->toBeNull();
});

test('non-recipient cannot remove a share', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    $share = Share::factory()->accepted()->create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
    ]);

    $this->actingAs($intruder);

    Livewire\Livewire::test(IncomingShares::class)
        ->call('remove', $share->id)
        ->assertForbidden();
});
