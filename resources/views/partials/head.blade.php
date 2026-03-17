<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $siteName = config('app.name', 'Notebud');
    $appUrl = rtrim((string) config('app.url', ''), '/');
    $defaultTitle = filled($title ?? null) ? $title.' - '.$siteName : $siteName;
    $metaTitle = trim($__env->yieldContent('meta_title')) ?: $defaultTitle;
    $metaDescription = trim($__env->yieldContent('meta_description')) ?: 'Share Notes & Files, Zero Hassle. A minimal, secure platform for your temporary pastes, notes, and file drops.';
    $metaImage = trim($__env->yieldContent('meta_image')) ?: url('/og-image.png');
    $canonicalOverride = trim($__env->yieldContent('canonical_url'));
    $requestPath = trim(request()->getPathInfo(), '/');
    $defaultCanonicalUrl = filled($appUrl)
        ? ($requestPath === '' ? $appUrl : $appUrl.'/'.$requestPath)
        : url()->current();
    $canonicalUrl = $canonicalOverride ?: $defaultCanonicalUrl;
    $metaKeywords = trim($__env->yieldContent('meta_keywords')) ?: null;
@endphp

<title>
    {{ $metaTitle }}
</title>

<x-seo-meta
    :title="$metaTitle"
    :description="$metaDescription"
    :url="$canonicalUrl"
    :image="$metaImage"
    :site-name="$siteName"
    :keywords="$metaKeywords"
/>

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
