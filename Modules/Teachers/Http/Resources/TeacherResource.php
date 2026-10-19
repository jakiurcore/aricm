<?php

namespace Modules\Teachers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Teachers\Models\Teacher;

/**
 * @mixin Teacher
 */
class TeacherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name_bn' => $this->name_bn, 'name_en' => $this->name_en, 'designation' => $this->designation, 'phone' => $this->phone,
            'is_active' => $this->is_active, 'user_id' => $this->user_id, 'login_email' => $this->user?->email, 'assignments_count' => $this->whenCounted('assignments'),
        ];
    }
}
