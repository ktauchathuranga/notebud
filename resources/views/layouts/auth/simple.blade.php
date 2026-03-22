<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @section('meta_robots', 'noindex, nofollow')
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        <script>
            (() => {
                const renderTurnstileWidgets = () => {
                    if (typeof window.turnstile === 'undefined') {
                        return;
                    }

                    document.querySelectorAll('.cf-turnstile').forEach((container) => {
                        if (container.querySelector('iframe')) {
                            return;
                        }

                        if (container.dataset.turnstileWidgetId) {
                            return;
                        }

                        const sitekey = container.dataset.sitekey;

                        if (!sitekey) {
                            return;
                        }

                        const widgetId = window.turnstile.render(container, {
                            sitekey,
                        });

                        container.dataset.turnstileWidgetId = String(widgetId);
                    });
                };

                window.notebudTurnstileOnload = renderTurnstileWidgets;

                document.addEventListener('DOMContentLoaded', renderTurnstileWidgets);
                document.addEventListener('livewire:navigated', renderTurnstileWidgets);
            })();
        </script>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=notebudTurnstileOnload&render=explicit" async defer></script>
        @fluxScripts
    </body>
</html>
