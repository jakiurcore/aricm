<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" data-lang="{{ app()->getLocale() }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', setting('institute_name_'.app()->getLocale(), config('app.name')))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Noto+Serif+Bengali:wght@500;600;700&family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <script>try{var t=localStorage.getItem('arm_theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t)}}catch(e){}</script>
    @stack('meta')
    @vite('resources/css/app.css')
    {{ Modules::vite('core', 'resources/js/app.ts') }}
    @stack('head')
</head>
<body class="min-h-screen bg-bg text-base text-ink antialiased">
    @yield('body')
    @stack('scripts')
</body>
</html>
