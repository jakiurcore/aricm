<?php

namespace Modules\Admission\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admission\Models\Inquiry;
use Modules\Admission\Services\AdmissionSettings;

class InquiryController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('admission.view'), 403);

        $inquiries = Inquiry::query()
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('search')->toString(), fn ($query, string $term) => $query->where(fn ($inner) => $inner->where('student_name', 'like', "%{$term}%")->orWhere('guardian_name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")))
            ->orderByDesc('id')->paginate(20);

        return response()->json(['data' => $inquiries->items(), 'meta' => ['current_page' => $inquiries->currentPage(), 'last_page' => $inquiries->lastPage(), 'total' => $inquiries->total(), 'per_page' => $inquiries->perPage()]]);
    }

    public function update(Request $request, Inquiry $inquiry): JsonResponse
    {
        abort_unless($request->user()->can('admission.manage'), 403);

        $inquiry->update($request->validate(['status' => ['required', Rule::in(Inquiry::STATUSES)], 'admin_note' => ['nullable', 'string', 'max:1000']]));

        return response()->json(['data' => $inquiry]);
    }

    public function destroy(Request $request, Inquiry $inquiry): JsonResponse
    {
        abort_unless($request->user()->can('admission.manage'), 403);

        $inquiry->delete();

        return response()->json(null, 204);
    }

    public function settings(Request $request, AdmissionSettings $settings): JsonResponse
    {
        abort_unless($request->user()->can('admission.view'), 403);

        return response()->json(['data' => $settings->all()]);
    }

    public function updateSettings(Request $request, AdmissionSettings $settings): JsonResponse
    {
        abort_unless($request->user()->can('admission.manage'), 403);

        $settings->update($request->validate([
            'banner_enabled' => ['boolean'],
            'banner_title_bn' => ['required', 'string', 'max:120'], 'banner_title_en' => ['nullable', 'string', 'max:120'],
            'admission_start' => ['nullable', 'date'], 'class_start' => ['nullable', 'date'],
            'free_class_note_bn' => ['nullable', 'string', 'max:200'], 'free_class_note_en' => ['nullable', 'string', 'max:200'],
            'classes' => ['array', 'max:30'], 'classes.*' => ['string', 'max:60'],
            'program_bn' => ['nullable', 'string', 'max:200'], 'program_en' => ['nullable', 'string', 'max:200'],
            'content_bn' => ['nullable', 'string', 'max:5000'], 'content_en' => ['nullable', 'string', 'max:5000'],
        ]));

        return response()->json(['data' => $settings->all()]);
    }
}
