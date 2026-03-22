@section('meta_title', 'Privacy & Terms - Notebud')
@section('meta_description', 'Privacy policy and terms of use for Notebud. We keep it simple — no email, no tracking, just your notes and files.')
@section('canonical_url', route('legal'))

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div class="relative overflow-hidden">
            <header class="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-6 lg:px-10">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <x-app-logo-icon class="size-8 fill-current text-zinc-900 dark:text-zinc-100" />
                    <span class="text-lg font-semibold tracking-tight">Notebud</span>
                </a>

                <div class="flex items-center gap-3">
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

            <main class="mx-auto w-full max-w-4xl px-6 pb-16 lg:px-10 lg:pb-24">
                <div class="py-8 lg:py-14">
                    <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Privacy & Terms</h1>
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">Last updated: {{ now()->format('F j, Y') }}</p>
                    <p class="mt-4 text-base text-zinc-600 dark:text-zinc-300">
                        Notebud is built for university students who need a simple way to save and share notes from lab computers. We believe in keeping things transparent — here's exactly what we do (and don't do) with your data.
                    </p>
                </div>

                <div class="space-y-10">

                    {{-- What We Collect --}}
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                        <h2 class="text-xl font-semibold tracking-tight">What we collect</h2>
                        <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>When you use Notebud, we store only what's necessary to make the app work:</p>
                            <ul class="list-disc space-y-2 pl-5">
                                <li><strong>Username and password</strong> — your password is securely hashed and never stored in plain text.</li>
                                <li><strong>Notes</strong> — the markdown notes you create.</li>
                                <li><strong>Files</strong> — any files you upload.</li>
                                <li><strong>Profile avatar</strong> — if you choose to upload one.</li>
                                <li><strong>Recovery codes</strong> — generated at registration so you can recover your account without email.</li>
                            </ul>
                        </div>
                    </section>

                    {{-- What We Don't Collect --}}
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                        <h2 class="text-xl font-semibold tracking-tight">What we don't collect</h2>
                        <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>We intentionally skip the stuff most services ask for:</p>
                            <ul class="list-disc space-y-2 pl-5">
                                <li><strong>No email address</strong> — we don't ask for one, ever.</li>
                                <li><strong>No phone number</strong> — no OTP, no SMS verification.</li>
                                <li><strong>No analytics or tracking</strong> — we don't use Google Analytics, tracking pixels, or any third-party analytics.</li>
                                <li><strong>No device information</strong> — we don't collect browser fingerprints, IP logs, or device identifiers.</li>
                            </ul>
                        </div>
                    </section>

                    {{-- Cookies --}}
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                        <h2 class="text-xl font-semibold tracking-tight">Cookies</h2>
                        <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>Notebud uses only <strong>essential cookies</strong> required to keep you logged in (session cookies). We don't use advertising cookies, tracking cookies, or any third-party cookies.</p>
                        </div>
                    </section>

                    {{-- Sharing --}}
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                        <h2 class="text-xl font-semibold tracking-tight">Sharing</h2>
                        <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>When you share a note or file with another user:</p>
                            <ul class="list-disc space-y-2 pl-5">
                                <li>The recipient gets a share request that they can <strong>accept or reject</strong>.</li>
                                <li>Only the content you explicitly share is visible to the recipient — nothing else from your account.</li>
                                <li>Your username is visible to people you share with (and vice versa).</li>
                            </ul>
                        </div>
                    </section>

                    {{-- Admin Access --}}
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                        <h2 class="text-xl font-semibold tracking-tight">Admin access</h2>
                        <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>Administrators can see limited account information for platform management:</p>
                            <ul class="list-disc space-y-2 pl-5">
                                <li>Your username, note count, and storage usage.</li>
                                <li>Admins can delete accounts if necessary (e.g., abuse or policy violations).</li>
                            </ul>
                            <p>Admins <strong>cannot</strong> read the contents of your notes or download your files.</p>
                        </div>
                    </section>

                    {{-- Your Data --}}
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                        <h2 class="text-xl font-semibold tracking-tight">Your data, your control</h2>
                        <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <ul class="list-disc space-y-2 pl-5">
                                <li>You can <strong>delete your account</strong> at any time from your account settings. This permanently removes your username, notes, files, and all associated data.</li>
                                <li>You can <strong>update your username and password</strong> whenever you want.</li>
                                <li>You can <strong>delete individual notes and files</strong> at any time.</li>
                            </ul>
                        </div>
                    </section>

                    {{-- Data Storage --}}
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                        <h2 class="text-xl font-semibold tracking-tight">Data storage</h2>
                        <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>Your data is stored on secure cloud infrastructure. Files and notes are stored separately from authentication data. We take reasonable precautions to protect your data, but no system is 100% secure.</p>
                        </div>
                    </section>

                    {{-- Terms of Use --}}
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                        <h2 class="text-xl font-semibold tracking-tight">Terms of use</h2>
                        <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>By using Notebud, you agree to the following:</p>
                            <ul class="list-disc space-y-2 pl-5">
                                <li><strong>Academic use only.</strong> Notebud is intended for fair academic use. Do not use it for cheating, plagiarism, or any form of academic dishonesty.</li>
                                <li><strong>No illegal content.</strong> Do not upload, store, or share content that is illegal, harmful, or violates your institution's code of conduct.</li>
                                <li><strong>Your responsibility.</strong> You are responsible for keeping your login credentials secure and for all activity under your account.</li>
                                <li><strong>Storage limits.</strong> Each account has a storage quota. If you exceed it, you won't be able to upload new files until you free up space.</li>
                                <li><strong>No warranty.</strong> Notebud is provided "as is" without warranties of any kind. We do our best, but we can't guarantee 100% uptime or zero data loss.</li>
                                <li><strong>Account termination.</strong> We reserve the right to delete accounts that violate these terms or are used for abuse.</li>
                            </ul>
                        </div>
                    </section>

                    {{-- CAPTCHA --}}
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                        <h2 class="text-xl font-semibold tracking-tight">CAPTCHA protection</h2>
                        <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>Login and registration pages are protected by <strong>Cloudflare Turnstile</strong> to prevent bots and abuse. This is a privacy-friendly CAPTCHA — it doesn't track you or use cookies for advertising. Turnstile is subject to <a href="https://www.cloudflare.com/privacypolicy/" target="_blank" rel="noopener noreferrer" class="font-medium underline underline-offset-2 hover:text-zinc-900 dark:hover:text-white">Cloudflare's privacy policy</a>.</p>
                        </div>
                    </section>

                    {{-- Changes --}}
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                        <h2 class="text-xl font-semibold tracking-tight">Changes to this page</h2>
                        <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>We may update this page from time to time. If we make significant changes, we'll update the "Last updated" date at the top. Your continued use of Notebud after changes means you're okay with the updated terms.</p>
                        </div>
                    </section>

                    {{-- Contact --}}
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/70 sm:p-8">
                        <h2 class="text-xl font-semibold tracking-tight">Contact</h2>
                        <div class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>If you have questions about this policy or your data, reach out at
                                <a href="mailto:contact@notebud.cc" class="inline-flex items-center rounded-md border border-zinc-200 bg-zinc-100/80 px-2 py-0.5 font-semibold text-zinc-900 transition hover:bg-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/70 dark:text-zinc-100 dark:hover:bg-zinc-700">
                                    contact@notebud.cc
                                </a>
                            </p>
                        </div>
                    </section>

                </div>
            </main>

            <footer class="border-t border-zinc-200 bg-white/70 px-6 py-6 dark:border-zinc-800 dark:bg-zinc-900/60 lg:px-10">
                <div class="mx-auto flex w-full max-w-4xl flex-col gap-4 text-sm text-zinc-600 dark:text-zinc-300 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-zinc-500 dark:text-zinc-400">
                        &copy; {{ date('Y') }} Notebud.
                        <span class="text-zinc-400 dark:text-zinc-500">Built by</span>
                        <a href="https://www.linkedin.com/in/ktauchathuranga/" target="_blank" rel="noopener noreferrer" class="font-normal text-zinc-500 no-underline underline-offset-2 hover:underline hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">Ashen Chathuranga</a>
                    </p>
                    <a href="{{ route('home') }}" class="hover:text-zinc-900 dark:hover:text-white">&larr; Back to home</a>
                </div>
            </footer>
        </div>

        @fluxScripts
    </body>
</html>
