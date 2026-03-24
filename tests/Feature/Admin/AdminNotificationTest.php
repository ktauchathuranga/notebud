<?php

use App\Livewire\Admin\Notifications\NotificationSend;
use App\Models\User;
use App\Notifications\AdminNotification;
use Illuminate\Support\Facades\Notification;

test('admin notifications page requires admin role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.notifications.index'))
        ->assertForbidden();
});

test('admin can send notification to all users including admins', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(NotificationSend::class)
        ->set('title', 'Maintenance Notice')
        ->set('message', 'The app will be briefly unavailable tonight.')
        ->set('priority', 'warning')
        ->set('target', 'all')
        ->call('send');

    Notification::assertSentTo([$admin, $userA, $userB], AdminNotification::class);
});

test('admin can send notification to selected users only', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(NotificationSend::class)
        ->set('title', 'Personal Update')
        ->set('message', 'This message is only for selected accounts.')
        ->set('priority', 'info')
        ->set('target', 'selected')
        ->set('selectedUserIds', [$userA->id])
        ->call('send');

    Notification::assertSentTo($userA, AdminNotification::class);
    Notification::assertNotSentTo($admin, AdminNotification::class);
    Notification::assertNotSentTo($userB, AdminNotification::class);
});

test('selected target requires at least one recipient', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(NotificationSend::class)
        ->set('title', 'Empty Selection')
        ->set('message', 'No users selected.')
        ->set('priority', 'info')
        ->set('target', 'selected')
        ->set('selectedUserIds', [])
        ->call('send')
        ->assertHasErrors('selectedUserIds');
});
