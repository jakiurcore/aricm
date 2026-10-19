<?php

namespace Modules\Results\Services;

use Modules\Exams\Models\Exam;
use Modules\Results\Models\ExamResult;

/**
 * Publishing is what makes results visible to students and the public. Unpublishing hides them again.
 */
class ResultPublisher
{
    public function publish(Exam $exam): void
    {
        $classIds = $exam->classes()->pluck('classes.id');
        $processed = ExamResult::query()->where('exam_id', $exam->id)->distinct()->pluck('class_id');

        abort_if($classIds->isEmpty() || $classIds->diff($processed)->isNotEmpty(), 422, __('results::messages.process_first'));

        $exam->forceFill(['result_published' => true, 'result_published_at' => now()])->save();

        activity('results')->performedOn($exam)->causedBy(auth()->user())->log('published');
    }

    public function unpublish(Exam $exam): void
    {
        $exam->forceFill(['result_published' => false, 'result_published_at' => null])->save();

        activity('results')->performedOn($exam)->causedBy(auth()->user())->log('unpublished');
    }
}
