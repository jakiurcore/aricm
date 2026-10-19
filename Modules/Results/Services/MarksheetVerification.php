<?php

namespace Modules\Results\Services;

use Modules\Core\Contracts\VerificationHandler;
use Modules\Core\Support\VerificationResult;
use Modules\Results\Support\MarksheetHash;

class MarksheetVerification implements VerificationHandler
{
    public function verify(string $hash): ?VerificationResult
    {
        $result = MarksheetHash::resolve($hash);

        if (! $result || ! $result->exam->result_published) {
            return null;
        }

        return new VerificationResult(__('results::messages.verify.title'), [
            __('results::messages.verify.student') => $result->student->displayName(),
            __('results::messages.verify.student_id') => $result->student->student_id,
            __('results::messages.verify.exam') => $result->exam->displayName(),
            __('results::messages.verify.class') => $result->student->schoolClass?->displayName() ?? '',
            __('results::messages.verify.gpa') => bn_number($result->gpa, 2),
            __('results::messages.verify.grade') => $result->grade,
            __('results::messages.verify.result') => $result->is_passed ? __('results::messages.passed') : __('results::messages.failed'),
        ]);
    }
}
