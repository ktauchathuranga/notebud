<?php

use App\Livewire\Notes\NoteCreate;
use App\Livewire\Notes\NoteEdit;
use App\Livewire\Notes\NoteIndex;
use App\Models\Note;
use App\Models\User;

test('notes index requires authentication', function () {
    $this->get('/notes')->assertRedirect('/login');
});

test('notes index page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/notes')
        ->assertOk();
});

test('user can create a note', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire\Livewire::test(NoteCreate::class)
        ->set('title', 'Test Note')
        ->set('content', '# Hello World')
        ->call('save');

    expect($user->notes()->count())->toBe(1);
    expect($user->notes()->first()->title)->toBe('Test Note');
});

test('user can view own note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/notes/{$note->id}")
        ->assertOk();
});

test('user cannot view another users note', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->get("/notes/{$note->id}")
        ->assertForbidden();
});

test('user can edit own note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire\Livewire::test(NoteEdit::class, ['note' => $note])
        ->set('title', 'Updated Title')
        ->set('content', 'Updated content')
        ->call('save');

    $note->refresh();
    expect($note->title)->toBe('Updated Title');
    expect($note->content)->toBe('Updated content');
});

test('user can delete own note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire\Livewire::test(NoteIndex::class)
        ->call('deleteNote', $note->id);

    expect(Note::find($note->id))->toBeNull();
});

test('user cannot delete another users note', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user);

    Livewire\Livewire::test(NoteIndex::class)
        ->call('deleteNote', $note->id)
        ->assertForbidden();
});

test('notes can be searched', function () {
    $user = User::factory()->create();
    Note::factory()->create(['user_id' => $user->id, 'title' => 'Laravel Notes']);
    Note::factory()->create(['user_id' => $user->id, 'title' => 'Vue Guide']);

    $this->actingAs($user);

    $component = Livewire\Livewire::test(NoteIndex::class)
        ->set('search', 'Laravel');

    $component->assertSee('Laravel Notes');
    $component->assertDontSee('Vue Guide');
});
