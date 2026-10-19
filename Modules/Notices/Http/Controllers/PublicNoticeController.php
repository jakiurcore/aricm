<?php

namespace Modules\Notices\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Notices\Enums\NoticeCategory;
use Modules\Notices\Services\NoticeDirectory;

class PublicNoticeController
{
    public function index(Request $request, NoticeDirectory $notices): View
    {
        $category = in_array($request->query('category'), NoticeCategory::values(), true) ? $request->query('category') : null;

        return view('notices::public.index', [
            'notices' => $notices->paginate($category, $request->string('q')->toString() ?: null),
            'category' => $category,
            'categories' => NoticeCategory::cases(),
        ]);
    }

    public function show(int $notice, NoticeDirectory $notices): View
    {
        return view('notices::public.show', ['notice' => $notices->find($notice) ?? abort(404)]);
    }
}
