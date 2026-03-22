@props([
    'title',
    'description',
    'url',
    'image',
    'type' => 'website',
    'siteName' => null,
    'keywords' => null,
    'robots' => 'index, follow',
    'twitterCard' => 'summary_large_image',
])

<meta name="robots" content="{{ $robots }}" />
<meta name="description" content="{{ $description }}" />
@if(filled($keywords))
    <meta name="keywords" content="{{ $keywords }}" />
@endif
<link rel="canonical" href="{{ $url }}" />

<meta property="og:type" content="{{ $type }}" />
<meta property="og:url" content="{{ $url }}" />
<meta property="og:title" content="{{ $title }}" />
<meta property="og:description" content="{{ $description }}" />
<meta property="og:image" content="{{ $image }}" />
@if(filled($siteName))
    <meta property="og:site_name" content="{{ $siteName }}" />
@endif

<meta name="twitter:card" content="{{ $twitterCard }}" />
<meta name="twitter:url" content="{{ $url }}" />
<meta name="twitter:title" content="{{ $title }}" />
<meta name="twitter:description" content="{{ $description }}" />
<meta name="twitter:image" content="{{ $image }}" />