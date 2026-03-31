<?php

use App\Livewire\NotificationBell;
use App\Models\Note;
use App\Models\Share;
use App\Models\User;
use App\Notifications\AdminNotification;
use App\Notifications\ShareRequestNotification;

test('notification bell renders for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire\Livewire::test(NotificationBell::class)
        ->assertOk();
});

test('mark all as read clears unread notifications', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $owner->id]);

    $share = Share::factory()->create([
        'shared_by' => $owner->id,
        'shared_with' => $recipient->id,
        'shareable_type' => Note::class,
        'shareable_id' => $note->id,
    ]);

    $recipient->notify(new ShareRequestNotification($share));

    expect($recipient->unreadNotifications()->count())->toBe(1);

    $this->actingAs($recipient);

    Livewire\Livewire::test(NotificationBell::class)
        ->call('markAllAsRead');

    expect($recipient->fresh()->unreadNotifications()->count())->toBe(0);
});

test('clear all removes all notifications', function () {
    $user = User::factory()->create();

    $user->notify(new AdminNotification(
        title: 'Test',
        message: 'Test message',
        priority: 'info',
        actionUrl: null,
        sentBy: 'admin',
    ));

    expect($user->notifications()->count())->toBe(1);

    $this->actingAs($user);

    Livewire\Livewire::test(NotificationBell::class)
        ->call('clearAll');

    expect($user->fresh()->notifications()->count())->toBe(0);
});

test('mark single notification as read', function () {
    $user = User::factory()->create();

    $user->notify(new AdminNotification(
        title: 'Test',
        message: 'Test message',
        priority: 'info',
        actionUrl: null,
        sentBy: 'admin',
    ));

    $notification = $user->unreadNotifications()->first();

    $this->actingAs($user);

    Livewire\Livewire::test(NotificationBell::class)
        ->call('markAsRead', $notification->id);

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});
