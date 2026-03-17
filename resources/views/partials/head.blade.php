<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:title" content="{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Notebud') : config('app.name', 'Notebud') }}" />
<meta property="og:description" content="Share Notes &amp; Files, Zero Hassle. A minimal, secure platform for your temporary pastes, notes, and file drops." />
<meta property="og:image" content="{{ url('/og-image.png') }}" />

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:url" content="{{ url()->current() }}" />
<meta name="twitter:title" content="{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Notebud') : config('app.name', 'Notebud') }}" />
<meta name="twitter:description" content="Share Notes &amp; Files, Zero Hassle. A minimal, secure platform for your temporary pastes, notes, and file drops." />
<meta name="twitter:image" content="{{ url('/og-image.png') }}" />

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    function parseMarkdown(text) {
        if (!text) return '';
        return marked.parse(text, { breaks: true, gfm: true });
    }
</script>
@fluxAppearance
