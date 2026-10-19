<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        {{-- Translations are inlined so the admin never renders before they are there (see Core's i18n helper). --}}
        <script>window.__I18N__ = @json(['locale' => app()->getLocale(), 'messages' => app(\Modules\Core\Services\JsTranslations::class)->for(app()->getLocale())], JSON_UNESCAPED_UNICODE);</script>

        {{-- What the signed-in user may do, so menus and buttons can hide what the server would refuse (the server still decides). --}}
        <script>window.__CAN__ = @json(auth()->user() ? auth()->user()->getAllPermissions()->pluck('name')->values() : []);</script>

        {{-- One call, so what the entries share is preloaded and styled once. The admin's own entry is the last one. --}}
        @viteReactRefresh
        @vite(['resources/css/app.css', ...$entries])

        @foreach ($urls as $url)
            <script type="module" src="{{ $url }}"></script>
        @endforeach
    </head>
    <body>
        <div id="admin" data-boot="{{ json_encode($boot) }}"></div>
    </body>
</html>
