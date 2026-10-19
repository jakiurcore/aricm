<?php

namespace Modules\Gallery\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Gallery\Models\Album;

class PublicGalleryController
{
    public function index(): View
    {
        return view('gallery::public.index', ['albums' => Album::query()->where('is_published', true)->has('photos')->withCount('photos')->orderBy('sort_order')->orderByDesc('album_date')->orderByDesc('id')->paginate(12)]);
    }

    public function show(int $album): View
    {
        $album = Album::query()->where('is_published', true)->with('photos')->findOrFail($album);

        return view('gallery::public.show', ['album' => $album]);
    }
}
