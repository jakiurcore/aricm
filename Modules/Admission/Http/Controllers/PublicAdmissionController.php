<?php

namespace Modules\Admission\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Admission\Http\Requests\InquiryRequest;
use Modules\Admission\Models\Inquiry;
use Modules\Admission\Services\AdmissionSettings;

class PublicAdmissionController
{
    public function show(AdmissionSettings $settings): View
    {
        return view('admission::public.show', ['settings' => $settings]);
    }

    public function store(InquiryRequest $request): RedirectResponse
    {
        // A filled honeypot was already rejected by validation; nothing is stored for it.
        Inquiry::create([...$request->safe()->only(['student_name', 'guardian_name', 'phone', 'desired_class', 'message']), 'status' => 'new', 'ip' => $request->ip()]);

        return redirect()->route('admission.public')->with('inquiry_sent', true);
    }
}
