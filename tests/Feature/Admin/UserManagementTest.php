<?php

use App\Livewire\Admin\Users\UserCreate;
use App\Livewire\Admin\Users\UserEdit;
use App\Livewire\Admin\Users\UserIndex;
use App\Models\User;

test('admin users page requires admin role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('admin can view users page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('admin can create user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(UserCreate::class)
        ->set('username', 'created_user')
        ->set('role', 'user')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('save');

    expect(User::where('username', 'created_user')->exists())->toBeTrue();
});

test('admin can create user with custom storage quota', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(UserCreate::class)
        ->set('username', 'quota_user')
        ->set('role', 'user')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->set('storage_quota_mb', '32')
        ->call('save');

    $created = User::where('username', 'quota_user')->firstOrFail();

    expect($created->storage_quota_bytes)->toBe(32 * 1024 * 1024);
});

test('admin can edit user role and password', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(UserEdit::class, ['user' => $target])
        ->set('username', 'updated_user')
        ->set('role', 'admin')
        ->set('password', 'NewPassword123!')
        ->set('password_confirmation', 'NewPassword123!')
        ->call('save');

    $target->refresh();

    expect($target->username)->toBe('updated_user');
    expect($target->role)->toBe('admin');
});

test('admin can edit user storage quota override', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(UserEdit::class, ['user' => $target])
        ->set('storage_quota_mb', '64')
        ->call('save');

    expect($target->fresh()->storage_quota_bytes)->toBe(64 * 1024 * 1024);
});

test('admin can apply quota to selected users', function () {
    $admin = User::factory()->admin()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(UserIndex::class)
        ->set('selectedUserIds', [$userA->id, $userB->id])
        ->set('bulkQuotaMb', '50')
        ->call('applyQuotaToSelected');

    expect($userA->fresh()->storage_quota_bytes)->toBe(50 * 1024 * 1024);
    expect($userB->fresh()->storage_quota_bytes)->toBe(50 * 1024 * 1024);
});

test('admin cannot change own role', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(UserEdit::class, ['user' => $admin])
        ->set('role', 'user')
        ->call('save')
        ->assertHasErrors('role');

    expect($admin->fresh()->role)->toBe('admin');
});

test('admin cannot delete own account from admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(UserIndex::class)
        ->call('deleteUser', $admin->id)
        ->assertHasErrors('delete');

    expect(User::whereKey($admin->id)->exists())->toBeTrue();
});

test('admin can delete another user', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(UserIndex::class)
        ->call('deleteUser', $target->id);

    expect(User::whereKey($target->id)->exists())->toBeFalse();
});
