<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="flex min-h-screen flex-col items-center justify-center p-6">
            <div class="text-center max-w-md">
                <div class="flex justify-center mb-6">
                    <x-app-logo-icon class="size-16 fill-current text-black dark:text-white" />
                </div>
                <h1 class="text-4xl font-semibold text-zinc-900 dark:text-white">Notebud</h1>
                <p class="mt-3 text-lg text-zinc-500 dark:text-zinc-400">Save notes, upload files, and share them with friends.</p>

                <div class="mt-8 flex items-center justify-center gap-4">
                    @auth
                        <a href="{{ route('notes.index') }}" class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            Go to Notes
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg border border-zinc-300 px-6 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                                Sign up
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
