<?php

use App\Http\Controllers\FileDownloadController;
use App\Livewire\Admin\Notifications\NotificationSend;
use App\Livewire\Admin\Users\UserCreate;
use App\Livewire\Admin\Users\UserEdit;
use App\Livewire\Admin\Users\UserIndex;
use App\Livewire\Auth\RecoverAccount;
use App\Livewire\Auth\RecoveryCodeHandoff;
use App\Livewire\Files\FileIndex;
use App\Livewire\Files\FileUpload;
use App\Livewire\Notes\NoteCreate;
use App\Livewire\Notes\NoteEdit;
use App\Livewire\Notes\NoteIndex;
use App\Livewire\Notes\NoteShow;
use App\Livewire\Shares\IncomingShares;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/sitemap.xml', function () {
    $routes = ['home', 'login', 'register', 'recovery.recover'];

    $urls = collect($routes)
        ->filter(fn (string $name) => Route::has($name))
        ->map(function (string $name) {
            return [
                'loc' => URL::route($name),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => $name === 'home' ? '1.0' : '0.7',
            ];
        })
        ->values();

    return response()
        ->view('sitemap.xml', ['urls' => $urls])
        ->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

Route::middleware('guest')->group(function () {
    Route::livewire('recover-account', RecoverAccount::class)
        ->middleware('throttle:5,1')
        ->name('recovery.recover');
});

Route::middleware(['auth'])->group(function () {
    Route::livewire('auth/recovery-codes', RecoveryCodeHandoff::class)->name('recovery-codes.handoff');
});

Route::middleware(['auth', 'recovery-codes.handoff'])->group(function () {
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

    // Admin
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('users', UserIndex::class)->name('users.index');
        Route::livewire('users/create', UserCreate::class)->name('users.create');
        Route::livewire('users/{user}/edit', UserEdit::class)->name('users.edit');

        Route::livewire('notifications', NotificationSend::class)->name('notifications.index');
    });
});

require __DIR__.'/settings.php';
