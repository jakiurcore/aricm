<?php

namespace Modules\StudentPortal\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Notices\Services\NoticeDirectory;

class NoticeController
{
    public function index(NoticeDirectory $notices): View
    {
        return view('student-portal::pages.notices.index', ['notices' => $notices->paginate(null, null, 15)]);
    }

    public function show(int $notice, NoticeDirectory $notices): View
    {
        return view('student-portal::pages.notices.show', ['notice' => $notices->find($notice) ?? abort(404)]);
    }
}
