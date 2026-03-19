@section('meta_title', 'Notebud - Notes and File Sharing for Students')
@section('meta_description', 'Notebud helps students write markdown notes, upload files, and share resources by username in one focused workspace.')
@section('meta_image', url('/og-image.png'))
@section('canonical_url', route('home'))
@section('meta_keywords', 'student notes, markdown notes, file sharing, class collaboration, notebud')

@php
    $homeStructuredData = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Notebud',
            'url' => route('home'),
            'description' => 'Notebud helps students write markdown notes, upload files, and share resources by username in one focused workspace.',
            'inLanguage' => 'en',
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'Notebud',
            'applicationCategory' => 'EducationalApplication',
            'operatingSystem' => 'Web',
            'url' => route('home'),
            'description' => 'A focused student workspace for markdown notes, file uploads, and username-based sharing. Free to use, with optional sponsorship support.',
            'isAccessibleForFree' => true,
            'offers' => [
                '@type' => 'Offer',
                'price' => '0.00',
                'priceCurrency' => 'USD',
            ],
        ],
    ];
@endphp

@section('structured_data')
{!! json_encode($homeStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
@endsection

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-cyan-200/50 blur-3xl dark:bg-cyan-900/20"></div>
                <div class="absolute right-0 top-48 h-80 w-80 rounded-full bg-amber-200/40 blur-3xl dark:bg-amber-900/20"></div>
                <div class="absolute bottom-10 left-1/3 h-64 w-64 rounded-full bg-emerald-200/40 blur-3xl dark:bg-emerald-900/20"></div>
            </div>

            <header class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6 lg:px-10">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <x-app-logo-icon class="size-8 fill-current text-zinc-900 dark:text-zinc-100" />
                    <span class="text-lg font-semibold tracking-tight">Notebud</span>
                </a>

                <div class="flex items-center gap-3">
                    <a href="https://ktauchathuranga.gumroad.com/l/sponsor" target="_blank" rel="noopener noreferrer" class="group hidden items-center justify-center gap-1.5 rounded-lg border border-pink-300/80 bg-zinc-50/90 px-4 py-2 text-sm font-medium text-zinc-700 shadow-md shadow-zinc-900/10 opacity-85 transition hover:opacity-100 hover:border-pink-400 hover:bg-pink-50 hover:shadow-lg dark:border-pink-800/70 dark:bg-zinc-900/70 dark:text-zinc-300 dark:shadow-black/40 dark:hover:bg-pink-950/20 dark:hover:shadow-black/60 sm:inline-flex motion-reduce:transform-none">
                        <svg aria-hidden="true" class="size-4 text-pink-500 transition-transform duration-200 ease-out group-hover:scale-110 motion-safe:group-hover:animate-[pulse_0.6s_ease-out_1] motion-reduce:animate-none" viewBox="0 0 16 16" fill="currentColor" shape-rendering="geometricPrecision">
                            <path d="m8 14.25.345.666a.75.75 0 0 1-.69 0l-.008-.004-.018-.01a7.152 7.152 0 0 1-.31-.17 22.055 22.055 0 0 1-3.434-2.414C2.045 10.731 0 8.35 0 5.5 0 2.836 2.086 1 4.25 1 5.797 1 7.153 1.802 8 3.02 8.847 1.802 10.203 1 11.75 1 13.914 1 16 2.836 16 5.5c0 2.85-2.045 5.231-3.885 6.818a22.066 22.066 0 0 1-3.744 2.584l-.018.01-.006.003h-.002ZM4.25 2.5c-1.336 0-2.75 1.164-2.75 3 0 2.15 1.58 4.144 3.365 5.682A20.58 20.58 0 0 0 8 13.393a20.58 20.58 0 0 0 3.135-2.211C12.92 9.644 14.5 7.65 14.5 5.5c0-1.836-1.414-3-2.75-3-1.373 0-2.609.986-3.029 2.456a.749.749 0 0 1-1.442 0C6.859 3.486 5.623 2.5 4.25 2.5Z" />
                        </svg>
                        Sponsor
                    </a>
                    @auth
                        <a href="{{ route('notes.index') }}" class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">
                            Go to Notes
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-900">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">
                                Get Started
                            </a>
                        @endif
                    @endauth
                </div>
            </header>

            <main class="mx-auto w-full max-w-6xl px-6 pb-16 lg:px-10 lg:pb-24">
                <section class="grid gap-10 py-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:py-14">
                    <div>
                        <p class="inline-flex items-center rounded-full border border-cyan-300/70 bg-cyan-100/60 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-800 dark:border-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-200">
                            Built for students
                        </p>
                        <h1 class="mt-5 text-4xl font-semibold tracking-tight text-zinc-900 dark:text-white sm:text-5xl lg:text-6xl">
                            Keep class notes and shared files in one place.
                        </h1>
                        <p class="mt-5 max-w-2xl text-base leading-relaxed text-zinc-600 dark:text-zinc-300 sm:text-lg">
                            Notebud helps classmates write notes in markdown, upload files, and share resources by username without the clutter of chat threads and scattered links.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            @auth
                                <a href="{{ route('notes.index') }}" class="inline-flex items-center justify-center rounded-xl bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">
                                    Open Workspace
                                </a>
                            @else
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">
                                        Create Free Account
                                    </a>
                                @endif
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-zinc-300 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-900">
                                    Log In
                                </a>
                            @endauth
                        </div>

                        <div class="mt-7 grid max-w-xl gap-2 text-sm text-zinc-600 dark:text-zinc-300 sm:grid-cols-2">
                            <p class="rounded-lg bg-white/70 px-3 py-2 ring-1 ring-zinc-200 dark:bg-zinc-900/50 dark:ring-zinc-800">Markdown notes with code highlighting</p>
                            <p class="rounded-lg bg-white/70 px-3 py-2 ring-1 ring-zinc-200 dark:bg-zinc-900/50 dark:ring-zinc-800">Share notes and files by username</p>
                            <p class="rounded-lg bg-white/70 px-3 py-2 ring-1 ring-zinc-200 dark:bg-zinc-900/50 dark:ring-zinc-800">In-app request and response notifications</p>
                            <p class="rounded-lg bg-white/70 px-3 py-2 ring-1 ring-zinc-200 dark:bg-zinc-900/50 dark:ring-zinc-800">Profile avatar and account controls</p>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="absolute -inset-3 rounded-3xl bg-linear-to-br from-cyan-200 to-emerald-200 opacity-60 blur-xl dark:from-cyan-900/50 dark:to-emerald-900/50"></div>
                        <div class="relative rounded-3xl border border-zinc-200 bg-white p-4 shadow-xl dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="size-2.5 rounded-full bg-red-400"></span>
                                    <span class="size-2.5 rounded-full bg-amber-400"></span>
                                    <span class="size-2.5 rounded-full bg-emerald-400"></span>
                                </div>
                                <span class="text-xs font-medium text-zinc-500">Notebud Workspace</span>
                            </div>

                            <div class="mb-3 flex items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 p-2.5 dark:border-zinc-800 dark:bg-zinc-950">
                                <div class="min-w-0 flex-1 rounded-md border border-zinc-200 bg-white px-2.5 py-1.5 text-[11px] text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
                                    Search notes and files...
                                </div>
                                <span class="rounded-md bg-zinc-900 px-2 py-1 text-[10px] font-semibold text-white dark:bg-zinc-100 dark:text-zinc-900">New Note</span>
                                <span class="rounded-md border border-zinc-300 px-2 py-1 text-[10px] font-semibold text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">Upload</span>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-[1fr_0.95fr]">
                                <div class="space-y-3">
                                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-950">
                                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-500">My Notes</p>
                                        <div class="grid gap-2">
                                            <div class="rounded-lg bg-white px-3 py-2 text-xs ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                                                <p class="font-semibold text-zinc-700 dark:text-zinc-200">Database Indexing Basics</p>
                                                <p class="mt-1 text-[11px] text-zinc-500">B-tree, hash indexes, and tradeoffs.</p>
                                            </div>
                                            <div class="rounded-lg bg-white px-3 py-2 text-xs ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                                                <p class="font-semibold text-zinc-700 dark:text-zinc-200">Networking Week 3</p>
                                                <p class="mt-1 text-[11px] text-zinc-500">OSI summary and socket examples.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-950">
                                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-500">My Files</p>
                                        <div class="space-y-1.5 text-[11px]">
                                            <div class="flex items-center justify-between rounded-md bg-white px-2.5 py-1.5 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                                                <span class="truncate pr-2">week6-slides.pdf</span>
                                                <span class="text-zinc-500">1.2 MB</span>
                                            </div>
                                            <div class="flex items-center justify-between rounded-md bg-white px-2.5 py-1.5 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                                                <span class="truncate pr-2">lab-template.zip</span>
                                                <span class="text-zinc-500">860 KB</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3 rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-950">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-zinc-500">Shared with me</p>
                                    <div class="space-y-2">
                                        <div class="rounded-lg bg-white px-3 py-2 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                                            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-200">Lab report draft</p>
                                            <p class="mt-1 text-[11px] text-zinc-500">From `nimal`</p>
                                        </div>
                                        <div class="rounded-lg bg-white px-3 py-2 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                                            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-200">Exam checklist.md</p>
                                            <p class="mt-1 text-[11px] text-zinc-500">From `sara`</p>
                                        </div>
                                    </div>
                                    <div class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                        2 new notifications
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mt-8 rounded-3xl border border-zinc-200 bg-white/70 p-6 dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
                    <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">Everything you need for study collaboration</h2>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <article class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                            <h3 class="text-sm font-semibold">Markdown notes</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Write structured notes with markdown and code-friendly formatting.</p>
                        </article>
                        <article class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                            <h3 class="text-sm font-semibold">File upload and download</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Store class material and download it quickly when you need it.</p>
                        </article>
                        <article class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                            <h3 class="text-sm font-semibold">Username-based sharing</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Share with classmates directly by username without searching for emails.</p>
                        </article>
                        <article class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                            <h3 class="text-sm font-semibold">Incoming requests</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Review pending share requests and accept or reject them in one place.</p>
                        </article>
                        <article class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                            <h3 class="text-sm font-semibold">In-app notifications</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Get immediate updates when content is shared or responses come in.</p>
                        </article>
                        <article class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                            <h3 class="text-sm font-semibold">Profile control</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Set your avatar, update username, and manage account settings anytime.</p>
                        </article>
                    </div>
                </section>

                <section class="mt-8 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                        <h2 class="text-2xl font-semibold tracking-tight">How it works</h2>
                        <ol class="mt-6 space-y-4">
                            <li class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Step 1</p>
                                <p class="mt-1 text-sm">Create your account and start your first note.</p>
                            </li>
                            <li class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Step 2</p>
                                <p class="mt-1 text-sm">Upload class files and organize your study material.</p>
                            </li>
                            <li class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Step 3</p>
                                <p class="mt-1 text-sm">Share by username and track responses in notifications.</p>
                            </li>
                        </ol>
                    </div>

                    <div class="rounded-3xl border border-zinc-200 bg-linear-to-b from-zinc-900 to-zinc-800 p-6 text-zinc-100 dark:border-zinc-700 sm:p-8">
                        <p class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide">Why students pick Notebud</p>
                        <h2 class="mt-4 text-2xl font-semibold tracking-tight">Simple when class gets busy</h2>
                        <div class="mt-5 space-y-3 text-sm text-zinc-200">
                            <p class="rounded-lg bg-white/5 px-3 py-2">Find your notes and files quickly with search.</p>
                            <p class="rounded-lg bg-white/5 px-3 py-2">Share only what you need with request approval built in.</p>
                            <p class="rounded-lg bg-white/5 px-3 py-2">Keep your profile and account controls in your hands.</p>
                        </div>
                        <a href="https://github.com/ktauchathuranga/notebud" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex rounded-lg border border-white/20 px-4 py-2 text-xs font-semibold uppercase tracking-wide transition hover:bg-white/10">
                            View Repository
                        </a>
                    </div>
                </section>

                <section class="mt-8 rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold tracking-tight">Support Notebud</h2>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">If Notebud helps your study workflow, you can support ongoing improvements.</p>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                                Support contact:
                                <a href="mailto:contact@notebud.cc" class="ms-1 inline-flex items-center rounded-md border border-zinc-200 bg-zinc-100/80 px-2 py-0.5 font-semibold text-zinc-900 transition hover:bg-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/70 dark:text-zinc-100 dark:hover:bg-zinc-700">
                                    contact@notebud.cc
                                </a>
                            </p>
                        </div>
                        <a href="https://ktauchathuranga.gumroad.com/l/sponsor" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-xl bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">
                            Sponsor the Project
                        </a>
                    </div>
                </section>
            </main>

            <footer class="border-t border-zinc-200 bg-white/70 px-6 py-6 dark:border-zinc-800 dark:bg-zinc-900/60 lg:px-10">
                <div class="mx-auto flex w-full max-w-6xl flex-col gap-4 text-sm text-zinc-600 dark:text-zinc-300 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-zinc-500 dark:text-zinc-400">
                        &copy; {{ date('Y') }} Notebud
                        <a href="https://github.com/ktauchathuranga/notebud/releases/tag/v1.0.0" target="_blank" rel="noopener noreferrer" class="font-normal text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300">(v1.0.0)</a>.
                        <span class="text-zinc-400 dark:text-zinc-500">Maintained by</span>
                        <a href="https://www.linkedin.com/in/ktauchathuranga/" target="_blank" rel="noopener noreferrer" class="font-normal text-zinc-500 no-underline underline-offset-2 hover:underline hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">Ashen Chathuranga</a>
                    </p>
                    <div class="flex items-center gap-4">
                        <a href="https://github.com/ktauchathuranga/notebud" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-900 dark:hover:text-white">Repository</a>
                        <a href="https://ktauchathuranga.gumroad.com/l/sponsor" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-900 dark:hover:text-white">Sponsor</a>
                        <a href="mailto:contact@notebud.cc" class="hover:text-zinc-900 dark:hover:text-white">Contact</a>
                        @guest
                            <a href="{{ route('login') }}" class="hover:text-zinc-900 dark:hover:text-white">Log in</a>
                        @endguest
                    </div>
                </div>
            </footer>
        </div>

        @fluxScripts
    </body>
</html>
