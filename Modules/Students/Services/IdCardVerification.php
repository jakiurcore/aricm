<?php

namespace Modules\Students\Services;

use Modules\Core\Contracts\VerificationHandler;
use Modules\Core\Support\SignedHash;
use Modules\Core\Support\VerificationResult;
use Modules\Students\Enums\StudentStatus;
use Modules\Students\Models\Student;

class IdCardVerification implements VerificationHandler
{
    public function verify(string $hash): ?VerificationResult
    {
        $id = SignedHash::parse('student-id', $hash);
        $student = $id ? Student::query()->with(['schoolClass', 'section', 'session'])->find($id) : null;

        if (! $student || $student->status !== StudentStatus::Active) {
            return null;
        }

        return new VerificationResult(__('students::pdf.verify.title'), [
            __('students::pdf.verify.name') => $student->displayName(),
            __('students::pdf.verify.student_id') => $student->student_id,
            __('students::pdf.verify.class') => $student->schoolClass?->displayName().($student->section ? ' / '.$student->section->displayName() : ''),
            __('students::pdf.verify.session') => (string) $student->session?->name,
            __('students::pdf.verify.status') => $student->status->label(),
        ]);
    }
}
