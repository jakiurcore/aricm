<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Modules\Core\Http\Middleware\SetLocale;

class LocaleController
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, SetLocale::SUPPORTED, true), 404);

        $request->session()->put('locale', $locale);

        return back()->withCookie(Cookie::make('locale', $locale, 60 * 24 * 365));
    }
}
