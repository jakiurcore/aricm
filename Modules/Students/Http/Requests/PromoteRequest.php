<?php

namespace Modules\Students\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Students\Models\Student;
use Modules\Students\Services\PromotionService;

class PromoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('promote', Student::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1', 'max:500'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')],
            'session_id' => ['required', 'integer', Rule::exists('academic_sessions', 'id')],
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')],
            'section_id' => ['nullable', 'integer', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value && ! app(AcademicDirectory::class)->sectionBelongsToClass((int) $value, (int) $this->input('class_id'))) {
                    $fail(__('students::messages.section_not_in_class'));
                }
            }],
            'roll_mode' => ['required', Rule::in([PromotionService::ROLL_KEEP, PromotionService::ROLL_SEQUENTIAL, PromotionService::ROLL_CLEAR])],
        ];
    }
}
