<?php

namespace Modules\Diary\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Services\DiaryCompliance;
use Modules\Diary\Services\DiaryService;
use Modules\Diary\Services\DiarySettings;

class MonitorController
{
    public function compliance(Request $request, DiaryCompliance $compliance): JsonResponse
    {
        abort_unless($request->user()->can('diary.monitor'), 403);

        $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        return response()->json(['data' => $compliance->forDate(Carbon::parse($data['date'] ?? now()))]);
    }

    public function teachers(Request $request, DiaryCompliance $compliance): JsonResponse
    {
        abort_unless($request->user()->can('diary.monitor'), 403);

        $data = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from']]);
        $to = Carbon::parse($data['to'] ?? now());
        $from = Carbon::parse($data['from'] ?? $to->copy()->subDays(13));
        abort_if($from->diffInDays($to) > 92, 422);

        return response()->json(['data' => $compliance->byTeacher($from, $to)]);
    }

    public function entries(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('diary.monitor'), 403);

        $entries = DiaryEntry::query()->with(['teacher', 'subject', 'schoolClass', 'section'])->withCount(['remarks', 'acknowledgements as seen_count' => fn ($query) => $query->whereNotNull('seen_at'), 'acknowledgements as acknowledged_count' => fn ($query) => $query->whereNotNull('acknowledged_at')])
            ->when($request->string('date')->toString(), fn ($query, string $date) => $query->whereDate('entry_date', $date))
            ->when($request->integer('class_id'), fn ($query, int $id) => $query->where('class_id', $id))
            ->when($request->integer('section_id'), fn ($query, int $id) => $query->where('section_id', $id))
            ->when($request->integer('teacher_id'), fn ($query, int $id) => $query->where('teacher_id', $id))
            ->orderByDesc('entry_date')->orderBy('class_id')->orderBy('section_id')->orderBy('id')
            ->paginate(30);

        return response()->json([
            'data' => $entries->getCollection()->map(fn (DiaryEntry $entry): array => [
                'id' => $entry->id, 'date' => $entry->entry_date->toDateString(), 'status' => $entry->status->value, 'teacher' => $entry->teacher->displayName(), 'class' => $entry->schoolClass->displayName(), 'section' => $entry->section->displayName(),
                'subject' => $entry->subjectName(), 'lesson' => $entry->lesson, 'homework' => $entry->homework, 'note' => $entry->note, 'homework_due_date' => $entry->homework_due_date?->toDateString(),
                'edited' => $entry->last_edited_at !== null, 'remarks' => $entry->remarks_count, 'seen' => $entry->seen_count, 'acknowledged' => $entry->acknowledged_count,
            ])->values(),
            'meta' => ['current_page' => $entries->currentPage(), 'last_page' => $entries->lastPage(), 'total' => $entries->total(), 'per_page' => $entries->perPage()],
        ]);
    }

    /**
     * The revision trail of one entry (every create and edit, including edits after publishing).
     */
    public function revisions(Request $request, DiaryEntry $entry): JsonResponse
    {
        abort_unless($request->user()->can('diary.monitor'), 403);

        $rows = $entry->activitiesAsSubject()->with('causer')->latest()->get()->map(fn ($activity): array => [
            'id' => $activity->id, 'event' => $activity->event, 'at' => $activity->created_at->toIso8601String(), 'by' => $activity->causer?->name, 'changes' => $activity->attribute_changes?->toArray() ?? [],
        ]);

        return response()->json(['data' => $rows]);
    }

    public function destroy(Request $request, DiaryEntry $entry, DiaryService $diary): JsonResponse
    {
        abort_unless($request->user()->can('diary.manage'), 403);

        $diary->delete($request->user(), $entry);

        return response()->json(null, 204);
    }

    public function settings(Request $request, DiarySettings $settings): JsonResponse
    {
        abort_unless($request->user()->can('diary.monitor') || $request->user()->can('diary.manage'), 403);

        return response()->json(['data' => $settings->all()]);
    }

    public function updateSettings(Request $request, DiarySettings $settings): JsonResponse
    {
        abort_unless($request->user()->can('diary.manage'), 403);

        $settings->update($request->validate([
            'enabled' => ['required', 'boolean'], 'edit_window_hours' => ['required', 'integer', 'min:0', 'max:720'], 'backdate_days' => ['required', 'integer', 'min:0', 'max:30'],
            'max_attachments' => ['required', 'integer', 'min:0', 'max:20'], 'sms_enabled' => ['required', 'boolean'], 'sms_teacher_reminder' => ['required', 'boolean'],
        ]));

        activity('diary')->causedBy($request->user())->log('settings updated');

        return response()->json(['data' => $settings->all()]);
    }
}
