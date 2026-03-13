<?php

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

    Livewire\Livewire::test(App\Livewire\Admin\Users\UserCreate::class)
        ->set('username', 'created_user')
        ->set('role', 'user')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('save');

    expect(User::where('username', 'created_user')->exists())->toBeTrue();
});

test('admin can edit user role and password', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(App\Livewire\Admin\Users\UserEdit::class, ['user' => $target])
        ->set('username', 'updated_user')
        ->set('role', 'admin')
        ->set('password', 'NewPassword123!')
        ->set('password_confirmation', 'NewPassword123!')
        ->call('save');

    $target->refresh();

    expect($target->username)->toBe('updated_user');
    expect($target->role)->toBe('admin');
});

test('admin cannot change own role', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(App\Livewire\Admin\Users\UserEdit::class, ['user' => $admin])
        ->set('role', 'user')
        ->call('save')
        ->assertHasErrors('role');

    expect($admin->fresh()->role)->toBe('admin');
});

test('admin cannot delete own account from admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(App\Livewire\Admin\Users\UserIndex::class)
        ->call('deleteUser', $admin->id)
        ->assertHasErrors('delete');

    expect(User::whereKey($admin->id)->exists())->toBeTrue();
});

test('admin can delete another user', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire\Livewire::test(App\Livewire\Admin\Users\UserIndex::class)
        ->call('deleteUser', $target->id);

    expect(User::whereKey($target->id)->exists())->toBeFalse();
});
