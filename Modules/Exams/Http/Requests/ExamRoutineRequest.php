<?php

namespace Modules\Exams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Exams\Models\ExamSubject;

class ExamRoutineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('exam'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $exam = $this->route('exam');
        $subjectIds = ExamSubject::query()->where(['exam_id' => $exam->id, 'class_id' => $this->route('class')->id])->pluck('subject_id')->all();

        $date = ['required', 'date'];
        if ($exam->start_date) {
            $date[] = 'after_or_equal:'.$exam->start_date->toDateString();
        }
        if ($exam->end_date) {
            $date[] = 'before_or_equal:'.$exam->end_date->toDateString();
        }

        return [
            'routine' => ['present', 'array'],
            'routine.*.subject_id' => ['required', 'integer', 'distinct', Rule::in($subjectIds)],
            'routine.*.exam_date' => $date,
            'routine.*.start_time' => ['required', 'date_format:H:i'],
            'routine.*.end_time' => ['required', 'date_format:H:i', 'after:routine.*.start_time'],
            'routine.*.room' => ['nullable', 'string', 'max:50'],
        ];
    }
}
