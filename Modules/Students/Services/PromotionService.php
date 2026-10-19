<?php

namespace Modules\Students\Services;

use Illuminate\Support\Facades\DB;
use Modules\Students\Enums\StudentStatus;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudentEnrollment;

/**
 * Moves students to a new session/class/section, keeping the old placement as enrollment history.
 */
class PromotionService
{
    public const string ROLL_KEEP = 'keep';

    public const string ROLL_SEQUENTIAL = 'sequential';

    public const string ROLL_CLEAR = 'clear';

    /**
     * @param  list<int>  $studentIds
     * @return int Number of students promoted.
     */
    public function promote(array $studentIds, int $sessionId, int $classId, ?int $sectionId, string $rollMode = self::ROLL_SEQUENTIAL): int
    {
        return DB::transaction(function () use ($studentIds, $sessionId, $classId, $sectionId, $rollMode): int {
            $students = Student::query()
                ->whereIn('id', $studentIds)
                ->where('status', StudentStatus::Active->value)
                ->orderBy('class_id')->orderBy('roll')->orderBy('name_bn')
                ->lockForUpdate()
                ->get();

            $nextRoll = (int) Student::query()
                ->where(['session_id' => $sessionId, 'class_id' => $classId])
                ->when($sectionId, fn ($query) => $query->where('section_id', $sectionId))
                ->max('roll') + 1;

            foreach ($students as $student) {
                StudentEnrollment::query()
                    ->where(['student_id' => $student->id, 'session_id' => $student->session_id])
                    ->update(['status' => 'promoted']);

                $roll = match ($rollMode) {
                    self::ROLL_KEEP => $student->roll,
                    self::ROLL_CLEAR => null,
                    default => $nextRoll++,
                };

                // Clear the old roll first so the unique (session, class, section, roll) index never collides mid-way.
                $student->forceFill(['session_id' => $sessionId, 'class_id' => $classId, 'section_id' => $sectionId, 'roll' => $roll])->save();

                StudentEnrollment::query()->updateOrCreate(
                    ['student_id' => $student->id, 'session_id' => $sessionId],
                    ['class_id' => $classId, 'section_id' => $sectionId, 'roll' => $roll, 'status' => 'active'],
                );
            }

            return $students->count();
        });
    }
}
