<?php

namespace Modules\Students\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Students\Enums\Gender;
use Modules\Students\Enums\ResidencyType;
use Modules\Students\Models\Student;
use Modules\Students\Support\ImportResult;
use SplFileObject;
use Throwable;

/**
 * Imports students from a UTF-8 CSV (see StudentCsv::COLUMNS). The class and section columns hold the Bangla or
 * English name. With $dryRun nothing is written, so an admin can fix the file first.
 */
class StudentImporter
{
    private const array GENDERS = ['male' => 'male', 'female' => 'female', 'ছেলে' => 'male', 'মেয়ে' => 'female', 'ছাত্র' => 'male', 'ছাত্রী' => 'female', 'm' => 'male', 'f' => 'female'];

    public function __construct(protected AcademicDirectory $academic, protected StudentRegistrar $registrar) {}

    public function import(string $path, bool $dryRun = false): ImportResult
    {
        $session = $this->academic->currentSession();
        $classes = $this->academic->classes(false);
        $rows = [];
        $errors = [];

        foreach ($this->read($path) as $line => $record) {
            $result = $this->prepare($record, $classes, $session?->id);

            if (is_array($result) && isset($result['__errors'])) {
                $errors[$line] = $result['__errors'];
            } else {
                $rows[$line] = $result;
            }
        }

        $created = 0;

        if (! $dryRun && $errors === [] && $rows !== []) {
            $failedLine = null;

            try {
                DB::transaction(function () use ($rows, &$created, &$failedLine): void {
                    foreach ($rows as $line => $data) {
                        $failedLine = $line;
                        $this->registrar->register($data);
                        $created++;
                    }
                });
            } catch (Throwable) {
                $created = 0;
                $errors[$failedLine ?? 0] = [__('students::messages.import.save_failed')];
            }
        }

        return new ImportResult(count($rows), $created, $errors, $dryRun);
    }

    /**
     * @return \Generator<int, array<string, string>>
     */
    protected function read(string $path): \Generator
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);

        $header = null;

        foreach ($file as $index => $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            $row = array_map(fn (?string $cell): string => trim((string) $cell), $row);

            if ($header === null) {
                $row[0] = ltrim($row[0], StudentCsv::BOM);
                $header = array_map(fn (string $name): string => strtolower($name), $row);

                continue;
            }

            if (implode('', $row) === '') {
                continue;
            }

            yield $index + 1 => array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), ''));
        }
    }

    /**
     * @param  array<string, string>  $record
     * @param  iterable<SchoolClass>  $classes
     * @return array<string, mixed>
     */
    protected function prepare(array $record, iterable $classes, ?int $sessionId): array
    {
        $record['date_of_birth'] = $this->date($record['date_of_birth'] ?? '');
        $record['admission_date'] = $this->date($record['admission_date'] ?? '') ?: now()->toDateString();
        $record['gender'] = self::GENDERS[mb_strtolower($record['gender'] ?? '')] ?? ($record['gender'] ?? '');
        $record['residency_type'] = ($record['residency_type'] ?? '') ?: ResidencyType::NonResidential->value;

        $class = collect($classes)->first(fn (SchoolClass $candidate): bool => $this->same($candidate->name_bn, $record['class'] ?? '') || $this->same((string) $candidate->name_en, $record['class'] ?? ''));
        $section = $class?->sections->first(fn ($candidate): bool => $this->same($candidate->name_bn, $record['section'] ?? '') || $this->same((string) $candidate->name_en, $record['section'] ?? ''));

        $validator = Validator::make($record, [
            'name_bn' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'admission_date' => ['required', 'date'],
            'gender' => ['required', Rule::in(Gender::values())],
            'residency_type' => ['required', Rule::in(ResidencyType::values())],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
            'roll' => ['nullable', 'integer', 'min:1'],
        ]);

        $messages = $validator->errors()->all();

        if (! $class) {
            $messages[] = __('students::messages.import.unknown_class', ['name' => $record['class'] ?? '']);
        }

        if (($record['section'] ?? '') !== '' && $class && ! $section) {
            $messages[] = __('students::messages.import.unknown_section', ['name' => $record['section']]);
        }

        if (! $sessionId) {
            $messages[] = __('students::messages.import.no_session');
        }

        if ($class && ($record['roll'] ?? '') !== '' && Student::query()->where(['session_id' => $sessionId, 'class_id' => $class->id, 'section_id' => $section?->id, 'roll' => (int) $record['roll']])->exists()) {
            $messages[] = __('students::messages.import.roll_taken', ['roll' => $record['roll']]);
        }

        if ($messages !== []) {
            return ['__errors' => $messages];
        }

        return [
            ...collect($record)->only(['name_bn', 'name_en', 'father_name', 'mother_name', 'guardian_name', 'guardian_phone', 'guardian_relation', 'date_of_birth', 'gender', 'blood_group', 'religion', 'present_address', 'permanent_address', 'admission_date', 'residency_type'])
                ->map(fn (string $value): ?string => $value === '' ? null : $value)->all(),
            'session_id' => $sessionId,
            'class_id' => $class->id,
            'section_id' => $section?->id,
            'roll' => ($record['roll'] ?? '') === '' ? null : (int) $record['roll'],
        ];
    }

    protected function same(string $a, string $b): bool
    {
        return $b !== '' && mb_strtolower(trim($a)) === mb_strtolower(trim($b));
    }

    /**
     * Accepts YYYY-MM-DD, DD-MM-YYYY and DD/MM/YYYY (also with Bangla digits). Returns '' when it cannot be read.
     */
    protected function date(string $value): string
    {
        $value = strtr(trim($value), ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
            } catch (Throwable) {
                continue;
            }

            if ($date !== false && $date->format($format) === $value) {
                return $date->toDateString();
            }
        }

        return '';
    }
}
