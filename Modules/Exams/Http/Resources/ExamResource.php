<?php

namespace Modules\Exams\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Exams\Models\Exam;

/**
 * @mixin Exam
 */
class ExamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->displayName(),
            'name_bn' => $this->name_bn,
            'name_en' => $this->name_en,
            'session_id' => $this->session_id,
            'session_name' => $this->whenLoaded('session', fn () => $this->session->name),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status->value,
            'admit_card_released' => $this->admit_card_released,
            'result_published' => (bool) ($this->result_published ?? false),
            'subjects_count' => $this->whenLoaded('subjects', fn () => $this->subjects->pluck('subject_id')->unique()->count()),
            'grading_scale_id' => $this->grading_scale_id,
            'description' => $this->description,
            'class_ids' => $this->whenLoaded('classes', fn () => $this->classes->pluck('id')->values()),
            'classes' => $this->whenLoaded('classes', fn () => $this->classes->map(fn ($class): array => ['id' => $class->id, 'name' => $class->displayName()])->values()),
        ];
    }
}
