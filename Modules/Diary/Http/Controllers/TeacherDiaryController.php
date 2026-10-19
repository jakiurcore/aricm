<?php

namespace Modules\Diary\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Diary\Enums\PhraseField;
use Modules\Diary\Enums\RemarkKind;
use Modules\Diary\Http\Requests\SaveEntryRequest;
use Modules\Diary\Models\DiaryAttachment;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Models\DiaryStudentRemark;
use Modules\Diary\Models\QuickPhrase;
use Modules\Diary\Services\DiaryService;
use Modules\Diary\Services\DiarySettings;
use Modules\Students\Models\Student;
use Modules\Teachers\Services\TeacherScope;

/**
 * The teacher's diary endpoints (JSON). The pages that use them live in the TeacherPanel module.
 */
class TeacherDiaryController
{
    public function __construct(protected DiaryService $diary, protected TeacherScope $scope) {}

    /**
     * Autosave and save: creates or updates the entry for the day. With "publish" it also publishes.
     */
    public function save(SaveEntryRequest $request): JsonResponse
    {
        $entry = $this->diary->save($request->user(), $request->safe()->except('publish'));

        if ($request->boolean('publish')) {
            $this->diary->publish($request->user(), $entry);
        }

        return response()->json(['data' => $this->present($entry->fresh(['attachments', 'remarks.student']))]);
    }

    /**
     * The teacher's entry for a section, subject and day (null when nothing was written yet).
     */
    public function show(Request $request): JsonResponse
    {
        $data = $request->validate(['class_id' => ['required', 'integer'], 'section_id' => ['required', 'integer'], 'subject_id' => ['nullable', 'integer'], 'date' => ['required', 'date_format:Y-m-d']]);
        $teacher = $this->scope->teacherFor($request->user());
        abort_unless($teacher, 403);

        $entry = DiaryEntry::query()->with(['attachments', 'remarks.student'])->where([
            'teacher_id' => $teacher->id, 'class_id' => $data['class_id'], 'section_id' => $data['section_id'], 'subject_id' => $data['subject_id'] ?? null,
        ])->whereDate('entry_date', $data['date'])->first();

        return response()->json(['data' => $entry ? $this->present($entry) : null]);
    }

    public function publish(Request $request, DiaryEntry $entry): JsonResponse
    {
        $this->diary->publish($request->user(), $entry);

        return response()->json(['data' => $this->present($entry->fresh(['attachments', 'remarks.student']))]);
    }

    public function destroy(Request $request, DiaryEntry $entry): JsonResponse
    {
        $this->diary->delete($request->user(), $entry);

        return response()->json(null, 204);
    }

    public function attach(Request $request, DiaryEntry $entry): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:'.implode(',', config('modules.diary.attachments.mimes')), 'max:'.app(DiarySettings::class)->maxAttachmentKb()]]);

        $attachment = $this->diary->addAttachment($request->user(), $entry, $request->file('file'));

        return response()->json(['data' => $this->attachment($attachment)], 201);
    }

    public function detach(Request $request, DiaryAttachment $attachment): JsonResponse
    {
        $this->diary->removeAttachment($request->user(), $attachment);

        return response()->json(null, 204);
    }

    public function remark(Request $request, DiaryEntry $entry): JsonResponse
    {
        $data = $request->validate(['student_id' => ['required', 'integer', 'exists:students,id'], 'kind' => ['required', 'in:'.implode(',', array_column(RemarkKind::cases(), 'value'))], 'text' => ['nullable', 'string', 'max:500']]);

        $remark = $this->diary->addRemark($request->user(), $entry, Student::findOrFail($data['student_id']), RemarkKind::from($data['kind']), (string) ($data['text'] ?? ''));

        return response()->json(['data' => $this->remarkRow($remark->load('student'))], 201);
    }

    public function unremark(Request $request, DiaryStudentRemark $remark): JsonResponse
    {
        $this->diary->removeRemark($request->user(), $remark);

        return response()->json(null, 204);
    }

    /**
     * Shared phrases plus the teacher's own, grouped by input.
     */
    public function phrases(Request $request): JsonResponse
    {
        $teacher = $this->scope->teacherFor($request->user());

        $phrases = QuickPhrase::query()->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('teacher_id')->when($teacher, fn ($inner) => $inner->orWhere('teacher_id', $teacher->id)))
            ->orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['data' => $phrases->groupBy(fn (QuickPhrase $phrase): string => $phrase->field->value)->map(fn ($group) => $group->map(fn (QuickPhrase $phrase): array => ['id' => $phrase->id, 'text' => $phrase->text_bn, 'own' => $phrase->teacher_id !== null])->values())]);
    }

    public function addPhrase(Request $request): JsonResponse
    {
        $data = $request->validate(['field' => ['required', 'in:'.implode(',', array_column(PhraseField::cases(), 'value'))], 'text_bn' => ['required', 'string', 'max:255']]);
        $teacher = $this->scope->teacherFor($request->user());
        abort_unless($teacher, 403);

        $phrase = QuickPhrase::create([...$data, 'teacher_id' => $teacher->id, 'sort_order' => 1000, 'is_active' => true]);

        return response()->json(['data' => ['id' => $phrase->id, 'text' => $phrase->text_bn, 'own' => true]], 201);
    }

    public function removePhrase(Request $request, QuickPhrase $phrase): JsonResponse
    {
        abort_unless($phrase->teacher_id !== null && $phrase->teacher_id === $this->scope->teacherFor($request->user())?->id, 403);

        $phrase->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(DiaryEntry $entry): array
    {
        return [
            'id' => $entry->id, 'status' => $entry->status->value, 'entry_date' => $entry->entry_date->toDateString(), 'class_id' => $entry->class_id, 'section_id' => $entry->section_id, 'subject_id' => $entry->subject_id,
            'lesson' => $entry->lesson, 'next_lesson' => $entry->next_lesson, 'homework' => $entry->homework, 'note' => $entry->note, 'homework_due_date' => $entry->homework_due_date?->toDateString(),
            'published_at' => $entry->published_at?->toIso8601String(), 'saved_at' => $entry->updated_at?->toIso8601String(), 'can_edit' => $this->diary->canEdit(request()->user(), $entry),
            'attachments' => $entry->attachments->map(fn (DiaryAttachment $attachment): array => $this->attachment($attachment))->values(),
            'remarks' => $entry->remarks->map(fn (DiaryStudentRemark $remark): array => $this->remarkRow($remark))->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function attachment(DiaryAttachment $attachment): array
    {
        return ['id' => $attachment->id, 'name' => $attachment->original_name, 'url' => $attachment->url(), 'is_image' => $attachment->isImage(), 'size' => $attachment->size];
    }

    /**
     * @return array<string, mixed>
     */
    protected function remarkRow(DiaryStudentRemark $remark): array
    {
        return ['id' => $remark->id, 'student_id' => $remark->student_id, 'student' => $remark->student?->displayName(), 'kind' => $remark->kind->value, 'text' => $remark->text];
    }
}
