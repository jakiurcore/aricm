<?php

namespace Modules\Students\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Students\Enums\Gender;
use Modules\Students\Enums\ResidencyType;
use Modules\Students\Enums\StudentStatus;
use Modules\Students\Models\Student;

class StudentRequest extends FormRequest
{
    public const array BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            ? $this->user()->can('update', $student)
            : $this->user()->can('create', Student::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'session_id' => ['required', 'integer', Rule::exists('academic_sessions', 'id')],
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')],
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id'), $this->sectionMatchesClass(...)],
            'roll' => [
                'nullable', 'integer', 'min:1',
                Rule::unique('students', 'roll')
                    ->where('session_id', $this->input('session_id'))
                    ->where('class_id', $this->input('class_id'))
                    ->where(fn ($query) => $this->filled('section_id') ? $query->where('section_id', $this->input('section_id')) : $query->whereNull('section_id'))
                    ->ignore($student),
            ],
            'name_bn' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
            'guardian_relation' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(Gender::values())],
            'blood_group' => ['nullable', Rule::in(self::BLOOD_GROUPS)],
            'religion' => ['nullable', 'string', 'max:50'],
            'present_address' => ['nullable', 'string', 'max:1000'],
            'permanent_address' => ['nullable', 'string', 'max:1000'],
            'admission_date' => ['required', 'date'],
            'status' => [$student ? 'required' : 'prohibited', Rule::enum(StudentStatus::class)],
            'residency_type' => ['required', Rule::in(ResidencyType::values())],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }

    /**
     * The section must belong to the chosen class: never trust the pair from the client.
     */
    protected function sectionMatchesClass(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value && ! app(AcademicDirectory::class)->sectionBelongsToClass((int) $value, (int) $this->input('class_id'))) {
            $fail(__('students::messages.section_not_in_class'));
        }
    }
}
