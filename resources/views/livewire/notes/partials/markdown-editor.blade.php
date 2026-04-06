<div>
    <flux:label>{{ __('Content') }} <span class="text-xs text-zinc-400">({{ __('Markdown supported') }})</span></flux:label>

    <div
        x-data="{
            tab: 'write',
            getEditor() {
                return this.$refs.editorContainer?.querySelector('textarea');
            },
            syncEditorValue(value, selectionStart, selectionEnd) {
                const textarea = this.getEditor();

                if (! textarea) {
                    return;
                }

                textarea.value = value;
                textarea.dispatchEvent(new Event('input', { bubbles: true }));

                this.$nextTick(() => {
                    textarea.focus();
                    textarea.setSelectionRange(selectionStart, selectionEnd);
                });
            },
            wrapSelection(prefix, suffix = prefix, placeholder = 'text') {
                const textarea = this.getEditor();

                if (! textarea) {
                    return;
                }

                const value = textarea.value ?? '';
                const start = textarea.selectionStart ?? 0;
                const end = textarea.selectionEnd ?? 0;
                const selectedText = value.slice(start, end);
                const content = selectedText || placeholder;
                const insertion = `${prefix}${content}${suffix}`;
                const nextValue = `${value.slice(0, start)}${insertion}${value.slice(end)}`;
                const nextSelectionStart = start + prefix.length;
                const nextSelectionEnd = nextSelectionStart + content.length;

                this.syncEditorValue(nextValue, nextSelectionStart, nextSelectionEnd);
            },
            insertLink() {
                const textarea = this.getEditor();

                if (! textarea) {
                    return;
                }

                const value = textarea.value ?? '';
                const start = textarea.selectionStart ?? 0;
                const end = textarea.selectionEnd ?? 0;
                const selectedText = value.slice(start, end) || 'link text';
                const url = 'https://';
                const insertion = `[${selectedText}](${url})`;
                const nextValue = `${value.slice(0, start)}${insertion}${value.slice(end)}`;
                const nextSelectionStart = start + selectedText.length + 3;
                const nextSelectionEnd = nextSelectionStart + url.length;

                this.syncEditorValue(nextValue, nextSelectionStart, nextSelectionEnd);
            },
            insertCodeBlock() {
                const textarea = this.getEditor();

                if (! textarea) {
                    return;
                }

                const value = textarea.value ?? '';
                const start = textarea.selectionStart ?? 0;
                const end = textarea.selectionEnd ?? 0;
                const selectedText = value.slice(start, end) || 'code';
                const insertion = `\`\`\`\n${selectedText}\n\`\`\``;
                const nextValue = `${value.slice(0, start)}${insertion}${value.slice(end)}`;
                const nextSelectionStart = start + 4;
                const nextSelectionEnd = nextSelectionStart + selectedText.length;

                this.syncEditorValue(nextValue, nextSelectionStart, nextSelectionEnd);
            },
            prefixLines(prefix, placeholder = 'item') {
                const textarea = this.getEditor();

                if (! textarea) {
                    return;
                }

                const value = textarea.value ?? '';
                const start = textarea.selectionStart ?? 0;
                const end = textarea.selectionEnd ?? 0;
                const selectedText = value.slice(start, end) || placeholder;
                const prefixed = selectedText
                    .split('\n')
                    .map((line) => `${prefix}${line}`)
                    .join('\n');
                const nextValue = `${value.slice(0, start)}${prefixed}${value.slice(end)}`;
                const nextSelectionStart = start + prefix.length;
                const nextSelectionEnd = start + prefixed.length;

                this.syncEditorValue(nextValue, nextSelectionStart, nextSelectionEnd);
            },
            insertNumberedList() {
                const textarea = this.getEditor();

                if (! textarea) {
                    return;
                }

                const value = textarea.value ?? '';
                const start = textarea.selectionStart ?? 0;
                const end = textarea.selectionEnd ?? 0;
                const selectedText = value.slice(start, end) || 'List item';
                const numbered = selectedText
                    .split('\n')
                    .map((line, index) => `${index + 1}. ${line}`)
                    .join('\n');
                const nextValue = `${value.slice(0, start)}${numbered}${value.slice(end)}`;
                const nextSelectionStart = start + 3;
                const nextSelectionEnd = start + numbered.length;

                this.syncEditorValue(nextValue, nextSelectionStart, nextSelectionEnd);
            }
        }"
        class="mt-1"
    >
        {{-- Unified GitHub-style container --}}
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            {{-- Header: tabs + toolbar --}}
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 bg-zinc-50 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-900/70">
                {{-- Write / Preview tabs --}}
                <div class="flex gap-1">
                    <button type="button" x-on:click="tab = 'write'"
                        class="rounded-md px-3 py-1 text-sm font-medium transition-colors"
                        :class="tab === 'write'
                            ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-800 dark:text-zinc-100'
                            : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'">
                        {{ __('Write') }}
                    </button>
                    <button type="button" x-on:click="tab = 'preview'"
                        class="rounded-md px-3 py-1 text-sm font-medium transition-colors"
                        :class="tab === 'preview'
                            ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-800 dark:text-zinc-100'
                            : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'">
                        {{ __('Preview') }}
                    </button>
                </div>

                {{-- Toolbar icons (visible only in write mode) --}}
                <div x-show="tab === 'write'" class="flex flex-wrap items-center gap-0.5">
                    {{-- Heading --}}
                    <flux:tooltip :content="__('Heading')" position="top">
                        <flux:button size="sm" type="button" variant="ghost" x-on:click.prevent="prefixLines('## ', 'Heading')" class="!px-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path d="M3.75 2a.75.75 0 0 1 .75.75V7h7V2.75a.75.75 0 0 1 1.5 0v10.5a.75.75 0 0 1-1.5 0V8.5h-7v4.75a.75.75 0 0 1-1.5 0V2.75A.75.75 0 0 1 3.75 2Z"/>
                            </svg>
                        </flux:button>
                    </flux:tooltip>

                    {{-- Bold --}}
                    <flux:tooltip :content="__('Bold')" position="top">
                        <flux:button size="sm" type="button" variant="ghost" x-on:click.prevent="wrapSelection('**', '**', 'bold text')" class="!px-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path d="M4 2h4.5a3.501 3.501 0 0 1 2.852 5.53A3.499 3.499 0 0 1 9.5 14H4a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1Zm1 7v3h4.5a1.5 1.5 0 0 0 0-3Zm3.5-2a1.5 1.5 0 0 0 0-3H5v3Z"/>
                            </svg>
                        </flux:button>
                    </flux:tooltip>

                    {{-- Italic --}}
                    <flux:tooltip :content="__('Italic')" position="top">
                        <flux:button size="sm" type="button" variant="ghost" x-on:click.prevent="wrapSelection('*', '*', 'italic text')" class="!px-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path d="M6 2.75A.75.75 0 0 1 6.75 2h6.5a.75.75 0 0 1 0 1.5h-2.505l-3.858 9H9.25a.75.75 0 0 1 0 1.5h-6.5a.75.75 0 0 1 0-1.5h2.505l3.858-9H6.75A.75.75 0 0 1 6 2.75Z"/>
                            </svg>
                        </flux:button>
                    </flux:tooltip>

                    {{-- Separator --}}
                    <div class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600" aria-hidden="true"></div>

                    {{-- Quote --}}
                    <flux:tooltip :content="__('Quote')" position="top">
                        <flux:button size="sm" type="button" variant="ghost" x-on:click.prevent="prefixLines('> ', 'Quoted text')" class="!px-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path d="M1.75 2.5h10.5a.75.75 0 0 1 0 1.5H1.75a.75.75 0 0 1 0-1.5Zm4 5h8.5a.75.75 0 0 1 0 1.5h-8.5a.75.75 0 0 1 0-1.5Zm0 5h8.5a.75.75 0 0 1 0 1.5h-8.5a.75.75 0 0 1 0-1.5ZM2.5 7.75v6a.75.75 0 0 1-1.5 0v-6a.75.75 0 0 1 1.5 0Z"/>
                            </svg>
                        </flux:button>
                    </flux:tooltip>

                    {{-- Code --}}
                    <flux:tooltip :content="__('Inline Code')" position="top">
                        <flux:button size="sm" type="button" variant="ghost" x-on:click.prevent="wrapSelection('`', '`', 'code')" class="!px-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path d="m11.28 3.22 4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.749.749 0 0 1-1.275-.326.749.749 0 0 1 .215-.734L13.94 8l-3.72-3.72a.749.749 0 0 1 .326-1.275.749.749 0 0 1 .734.215Zm-6.56 0a.751.751 0 0 1 1.042.018.751.751 0 0 1 .018 1.042L2.06 8l3.72 3.72a.749.749 0 0 1-.326 1.275.749.749 0 0 1-.734-.215L.47 8.53a.75.75 0 0 1 0-1.06Z"/>
                            </svg>
                        </flux:button>
                    </flux:tooltip>

                    {{-- Code Block --}}
                    <flux:tooltip :content="__('Code Block')" position="top">
                        <flux:button size="sm" type="button" variant="ghost" x-on:click.prevent="insertCodeBlock()" class="!px-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path d="M0 1.75C0 .784.784 0 1.75 0h12.5C15.216 0 16 .784 16 1.75v12.5A1.75 1.75 0 0 1 14.25 16H1.75A1.75 1.75 0 0 1 0 14.25Zm1.75-.25a.25.25 0 0 0-.25.25v12.5c0 .138.112.25.25.25h12.5a.25.25 0 0 0 .25-.25V1.75a.25.25 0 0 0-.25-.25Zm7.47 3.97a.75.75 0 0 1 1.06 0l2.25 2.25a.75.75 0 0 1 0 1.06l-2.25 2.25a.749.749 0 0 1-1.275-.326.749.749 0 0 1 .215-.734L10.94 8 9.22 6.28a.75.75 0 0 1 0-1.06ZM6.78 6.28a.75.75 0 0 0-1.06-1.06l-2.25 2.25a.75.75 0 0 0 0 1.06l2.25 2.25a.749.749 0 0 0 1.275-.326.749.749 0 0 0-.215-.734L5.06 8l1.72-1.72Z"/>
                            </svg>
                        </flux:button>
                    </flux:tooltip>

                    {{-- Link --}}
                    <flux:tooltip :content="__('Link')" position="top">
                        <flux:button size="sm" type="button" variant="ghost" x-on:click.prevent="insertLink()" class="!px-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path d="m7.775 3.275 1.25-1.25a3.5 3.5 0 1 1 4.95 4.95l-2.5 2.5a3.5 3.5 0 0 1-4.95 0 .751.751 0 0 1 .018-1.042.751.751 0 0 1 1.042-.018 1.998 1.998 0 0 0 2.83 0l2.5-2.5a2.002 2.002 0 0 0-2.83-2.83l-1.25 1.25a.751.751 0 0 1-1.042-.018.751.751 0 0 1-.018-1.042Zm-4.69 9.64a1.998 1.998 0 0 0 2.83 0l1.25-1.25a.751.751 0 0 1 1.042.018.751.751 0 0 1 .018 1.042l-1.25 1.25a3.5 3.5 0 1 1-4.95-4.95l2.5-2.5a3.5 3.5 0 0 1 4.95 0 .751.751 0 0 1-.018 1.042.751.751 0 0 1-1.042.018 1.998 1.998 0 0 0-2.83 0l-2.5 2.5a1.998 1.998 0 0 0 0 2.83Z"/>
                            </svg>
                        </flux:button>
                    </flux:tooltip>

                    {{-- Separator --}}
                    <div class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600" aria-hidden="true"></div>

                    {{-- Bulleted List --}}
                    <flux:tooltip :content="__('Bulleted List')" position="top">
                        <flux:button size="sm" type="button" variant="ghost" x-on:click.prevent="prefixLines('- ', 'List item')" class="!px-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path d="M5.75 2.5h8.5a.75.75 0 0 1 0 1.5h-8.5a.75.75 0 0 1 0-1.5Zm0 5h8.5a.75.75 0 0 1 0 1.5h-8.5a.75.75 0 0 1 0-1.5Zm0 5h8.5a.75.75 0 0 1 0 1.5h-8.5a.75.75 0 0 1 0-1.5ZM2 14a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm1-6a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM2 4a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/>
                            </svg>
                        </flux:button>
                    </flux:tooltip>

                    {{-- Numbered List --}}
                    <flux:tooltip :content="__('Numbered List')" position="top">
                        <flux:button size="sm" type="button" variant="ghost" x-on:click.prevent="insertNumberedList()" class="!px-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                <path d="M5 3.25a.75.75 0 0 1 .75-.75h8.5a.75.75 0 0 1 0 1.5h-8.5A.75.75 0 0 1 5 3.25Zm0 5a.75.75 0 0 1 .75-.75h8.5a.75.75 0 0 1 0 1.5h-8.5A.75.75 0 0 1 5 8.25Zm0 5a.75.75 0 0 1 .75-.75h8.5a.75.75 0 0 1 0 1.5h-8.5a.75.75 0 0 1-.75-.75ZM.924 10.32a.5.5 0 0 1-.851-.525l.001-.001.001-.002.002-.004.007-.011c.097-.144.215-.273.348-.384.228-.19.588-.392 1.068-.392.468 0 .858.181 1.126.484.259.294.377.673.377 1.038 0 .987-.686 1.495-1.156 1.845l-.047.035c-.303.225-.522.4-.654.597h1.357a.5.5 0 0 1 0 1H.5a.5.5 0 0 1-.5-.5c0-1.005.692-1.52 1.167-1.875l.035-.025c.531-.396.8-.625.8-1.078a.57.57 0 0 0-.128-.376C1.806 10.068 1.695 10 1.5 10a.658.658 0 0 0-.429.163.835.835 0 0 0-.144.153ZM2.003 2.5V6h.503a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1h.503V3.308l-.28.14a.5.5 0 0 1-.446-.895l1.003-.5a.5.5 0 0 1 .723.447Z"/>
                            </svg>
                        </flux:button>
                    </flux:tooltip>
                </div>
            </div>

            {{-- Content area --}}
            <div x-show="tab === 'write'" x-ref="editorContainer">
                <textarea wire:model="content" rows="15" placeholder="{{ __('Write your note in markdown...') }}"
                    class="w-full resize-y border-0 bg-transparent px-3 py-3 font-mono text-sm text-zinc-900 placeholder-zinc-400 focus:ring-0 focus:outline-none dark:text-zinc-100 dark:placeholder-zinc-500"></textarea>
            </div>

            <div x-show="tab === 'preview'" x-cloak>
                <div class="prose dark:prose-invert max-w-none p-4 min-h-60"
                     x-data="{ html: '' }"
                     x-init="$watch('$wire.content', async (val) => { html = await parseMarkdown(val || ''); }); html = await parseMarkdown($wire.content || '');"
                     x-html="html || '<p class=\'text-zinc-400\'>Nothing to preview...</p>'">
                </div>
            </div>
        </div>
    </div>
</div>