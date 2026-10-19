<?php

namespace Modules\Results\Support;

use Modules\Results\Models\ExamResult;

/**
 * The hash in a marksheet QR code: "{result id}-{hmac}", so it cannot be guessed or forged for another result.
 */
final class MarksheetHash
{
    public static function for(ExamResult $result): string
    {
        return $result->id.'-'.self::signature($result->id, $result->exam_id, $result->student_id);
    }

    public static function resolve(string $hash): ?ExamResult
    {
        [$id, $signature] = array_pad(explode('-', $hash, 2), 2, '');

        if (! ctype_digit($id)) {
            return null;
        }

        $result = ExamResult::query()->with(['exam', 'student.schoolClass', 'student.section'])->find((int) $id);

        return $result && hash_equals(self::signature($result->id, $result->exam_id, $result->student_id), $signature) ? $result : null;
    }

    private static function signature(int $id, int $examId, int $studentId): string
    {
        return substr(hash_hmac('sha256', "marksheet|{$id}|{$examId}|{$studentId}", (string) config('app.key')), 0, 16);
    }
}
