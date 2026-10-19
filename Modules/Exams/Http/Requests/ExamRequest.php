<?php

namespace Modules\Exams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Exams\Enums\ExamStatus;
use Modules\Exams\Models\Exam;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $exam instanceof Exam ? $this->user()->can('update', $exam) : $this->user()->can('create', Exam::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name_bn' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'session_id' => ['required', 'integer', Rule::exists('academic_sessions', 'id')],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(ExamStatus::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'class_ids' => ['array'],
            'class_ids.*' => ['integer', 'distinct', Rule::exists('classes', 'id')],
        ];
    }
}
