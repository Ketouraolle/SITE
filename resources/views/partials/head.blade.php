<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#fbf4f2">
<meta name="description" content="{{ config('shop.name') }} — commandez vos produits en ligne et finalisez sur WhatsApp.">

<title>{{ filled($title ?? null) ? $title.' · '.config('shop.name') : config('shop.name') }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600,700|great-vibes:400|montserrat:400,500,600,700,800&display=swap" rel="stylesheet" />
<link rel="icon" type="image/jpeg" href="/images/logo.jpeg">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
