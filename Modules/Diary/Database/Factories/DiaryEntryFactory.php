<?php

namespace Modules\Diary\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\Subject;
use Modules\Diary\Enums\DiaryStatus;
use Modules\Diary\Models\DiaryEntry;
use Modules\Teachers\Models\Teacher;

/**
 * @extends Factory<DiaryEntry>
 */
class DiaryEntryFactory extends Factory
{
    protected $model = DiaryEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => AcademicSession::factory(),
            'class_id' => SchoolClass::factory(),
            'section_id' => fn (array $attributes): int => Section::factory()->create(['class_id' => $attributes['class_id']])->id,
            'subject_id' => Subject::factory(),
            'teacher_id' => Teacher::factory(),
            'entry_date' => now()->toDateString(),
            'lesson' => 'আজ অধ্যায় ১ পড়ানো হয়েছে।',
            'homework' => 'পৃষ্ঠা ১০ থেকে ১২ লিখে আনবে।',
            'note' => null,
            'homework_due_date' => null,
            'status' => DiaryStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => DiaryStatus::Published, 'published_at' => now()]);
    }
}
