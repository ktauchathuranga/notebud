<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Save your recovery codes')" :description="__('Copy or download these one-time codes now. You will not see them again.')" />

    <div class="rounded-lg border border-amber-300/60 bg-amber-50 p-4 dark:border-amber-500/40 dark:bg-amber-900/20">
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach ($codes as $code)
                <code class="rounded bg-white px-3 py-2 text-sm font-semibold tracking-wide text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">{{ $code }}</code>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <flux:button variant="primary" wire:click="copyAndContinue" type="button" class="w-full">
            {{ __('Copy and continue') }}
        </flux:button>

        <flux:button variant="filled" wire:click="downloadAndContinue" type="button" class="w-full">
            {{ __('Download and continue') }}
        </flux:button>
    </div>

    @script
        <script>
            const normalizeDetail = (event) => Array.isArray(event.detail) ? event.detail[0] : event.detail;

            const fallbackCopy = (text) => {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            };

            window.addEventListener('recovery-codes-copy-and-continue', async (event) => {
                const detail = normalizeDetail(event);

                try {
                    await navigator.clipboard.writeText(detail.codes);
                } catch (_) {
                    fallbackCopy(detail.codes);
                }

                window.location.assign(detail.redirect);
            });

            window.addEventListener('recovery-codes-download-and-continue', (event) => {
                const detail = normalizeDetail(event);
                const blob = new Blob([detail.content], { type: 'text/plain;charset=utf-8' });
                const objectUrl = URL.createObjectURL(blob);
                const anchor = document.createElement('a');

                anchor.href = objectUrl;
                anchor.download = detail.filename;
                document.body.appendChild(anchor);
                anchor.click();
                anchor.remove();

                URL.revokeObjectURL(objectUrl);
                window.location.assign(detail.redirect);
            });
        </script>
    @endscript
</div>
