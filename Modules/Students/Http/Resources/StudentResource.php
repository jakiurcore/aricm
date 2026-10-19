<?php

namespace Modules\Students\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Students\Models\Student;

/**
 * @mixin Student
 */
class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'session_id' => $this->session_id,
            'class_id' => $this->class_id,
            'section_id' => $this->section_id,
            'class_name' => $this->whenLoaded('schoolClass', fn () => $this->schoolClass->displayName()),
            'section_name' => $this->whenLoaded('section', fn () => $this->section?->displayName()),
            'session_name' => $this->whenLoaded('session', fn () => $this->session->name),
            'roll' => $this->roll,
            'name_bn' => $this->name_bn,
            'name_en' => $this->name_en,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'guardian_name' => $this->guardian_name,
            'guardian_phone' => $this->guardian_phone,
            'guardian_relation' => $this->guardian_relation,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'birth_registration_no' => $this->birth_registration_no,
            'gender' => $this->gender->value,
            'blood_group' => $this->blood_group,
            'religion' => $this->religion,
            'present_address' => $this->present_address,
            'permanent_address' => $this->permanent_address,
            'admission_date' => $this->admission_date?->toDateString(),
            'status' => $this->status->value,
            'residency_type' => $this->residency_type->value,
            'photo_url' => $this->photo_url,
            'must_change_password' => $this->must_change_password,
            'latest_gpa' => $this->when(array_key_exists('latest_gpa', $this->getAttributes()), fn () => $this->getAttribute('latest_gpa')),
            'fee_badge' => $this->when(array_key_exists('fee_badge', $this->getAttributes()), fn () => $this->getAttribute('fee_badge')),
        ];
    }
}
