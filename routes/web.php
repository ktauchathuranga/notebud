<?php

use App\Http\Controllers\FileDownloadController;
use App\Livewire\Files\FileIndex;
use App\Livewire\Files\FileUpload;
use App\Livewire\Notes\NoteCreate;
use App\Livewire\Notes\NoteEdit;
use App\Livewire\Notes\NoteIndex;
use App\Livewire\Notes\NoteShow;
use App\Livewire\Shares\IncomingShares;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::redirect('dashboard', 'notes');

    // Notes
    Route::livewire('notes', NoteIndex::class)->name('notes.index');
    Route::livewire('notes/create', NoteCreate::class)->name('notes.create');
    Route::livewire('notes/{note}', NoteShow::class)->name('notes.show');
    Route::livewire('notes/{note}/edit', NoteEdit::class)->name('notes.edit');

    // Files
    Route::livewire('files', FileIndex::class)->name('files.index');
    Route::livewire('files/upload', FileUpload::class)->name('files.upload');
    Route::get('files/{file}/download', FileDownloadController::class)->name('files.download');

    // Shares
    Route::livewire('shares', IncomingShares::class)->name('shares.incoming');
});

require __DIR__.'/settings.php';
