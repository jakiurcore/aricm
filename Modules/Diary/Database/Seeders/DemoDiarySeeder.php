<?php

namespace Modules\Diary\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Diary\Enums\DiaryStatus;
use Modules\Diary\Enums\RemarkKind;
use Modules\Diary\Models\DiaryAcknowledgement;
use Modules\Diary\Models\DiaryEntry;
use Modules\Students\Models\Student;
use Modules\Teachers\Models\TeacherAssignment;

class DemoDiarySeeder extends Seeder
{
    private const array LESSONS = [
        'QURAN' => 'আজ সূরা আল-ফাতিহার তাজবিদসহ তিলাওয়াত অনুশীলন করানো হয়েছে।', 'ARABIC' => 'আজ নতুন আরবি শব্দ ও বাক্য গঠন শেখানো হয়েছে।', 'BANGLA' => 'আজ পাঠ্যবইয়ের নতুন গল্প পড়া ও শব্দার্থ শেখানো হয়েছে।',
        'ENGLISH' => 'Today we practised the alphabet, new words and short sentences.', 'MATH' => 'আজ যোগ ও বিয়োগের নিয়ম বোর্ডে অনুশীলন করানো হয়েছে।', 'MENTAL_MATH' => 'আজ দ্রুত যোগের মানসিক গণনা অনুশীলন করানো হয়েছে।',
        'ISLAM' => 'আজ নামাজের নিয়ম ও দোয়া শেখানো হয়েছে।', 'GK' => 'আজ আমাদের দেশ ও পরিবেশ সম্পর্কে আলোচনা হয়েছে।',
    ];

    private const array HOMEWORK = [
        'QURAN' => 'আজকের পড়া ৫ বার তিলাওয়াত করে আসবে।', 'ARABIC' => 'নতুন শব্দগুলো খাতায় ৩ বার লিখে আনবে।', 'BANGLA' => 'গল্পটি পড়ে আসবে এবং কঠিন শব্দ খাতায় লিখবে।',
        'ENGLISH' => 'Write the new words twice in your copy.', 'MATH' => 'অনুশীলনীর ১ থেকে ৫ নম্বর সমাধান করে আনবে।', 'MENTAL_MATH' => 'দৈনিক ৫ মিনিট যোগের অনুশীলন করবে।',
        'ISLAM' => 'দোয়াটি মুখস্থ করে আসবে।', 'GK' => '',
    ];

    /**
     * Two weeks of diary up to today: every subject written on most days (a few left missing or as drafts so the office
     * monitor has something to show), general notes from class teachers, some remarks, and guardians' acknowledgements.
     */
    public function run(SchoolCalendar $calendar, AcademicDirectory $academic): void
    {
        if (DiaryEntry::query()->exists()) {
            return;
        }

        // One transaction and no revision log: this is bulk demo data, not teachers editing.
        DB::transaction(fn () => activity()->withoutLogging(fn () => $this->seed($calendar, $academic)));
    }

    protected function seed(SchoolCalendar $calendar, AcademicDirectory $academic): void
    {

        $session = $academic->currentSession();
        $assignments = TeacherAssignment::query()->with(['subject', 'teacher'])->where('session_id', $session->id)->get();

        $subjectRows = $assignments->whereNotNull('subject_id');
        $classTeachers = $assignments->where('is_class_teacher', true);
        $created = collect();

        for ($offset = 13; $offset >= 0; $offset--) {
            $day = now()->subDays($offset)->startOfDay();

            if (! $calendar->isSchoolDay($day)) {
                continue;
            }

            foreach ($subjectRows as $assignment) {
                // A stable "random" so reseeding gives the same picture: about one in eight is missing today, fewer earlier.
                $roll = crc32("{$assignment->id}:{$day->toDateString()}") % 100;

                if ($roll < ($offset === 0 ? 14 : 4)) {
                    continue;
                }

                $code = $assignment->subject->code;
                $published = ! ($offset === 0 && $roll < 24);
                $homework = self::HOMEWORK[$code] ?? '';

                $created->push(DiaryEntry::create([
                    'session_id' => $session->id, 'class_id' => $assignment->class_id, 'section_id' => $assignment->section_id, 'subject_id' => $assignment->subject_id, 'teacher_id' => $assignment->teacher_id,
                    'entry_date' => $day->toDateString(), 'lesson' => self::LESSONS[$code] ?? null, 'homework' => $homework ?: null,
                    'homework_due_date' => $homework ? $calendar->nextSchoolDay($day)->toDateString() : null,
                    'status' => $published ? DiaryStatus::Published : DiaryStatus::Draft, 'published_at' => $published ? $day->copy()->setTime(13, 30) : null,
                ]));
            }

            if ($day->dayOfWeek === 4 || $offset === 0) {
                foreach ($classTeachers as $assignment) {
                    DiaryEntry::create([
                        'session_id' => $session->id, 'class_id' => $assignment->class_id, 'section_id' => $assignment->section_id, 'subject_id' => null, 'teacher_id' => $assignment->teacher_id,
                        'entry_date' => $day->toDateString(), 'note' => 'অভিভাবকগণ সন্তানের খাতা ও ব্যাগ নিয়মিত দেখবেন। আগামীকাল ছুটি, শনিবার যথাসময়ে মাদরাসায় আসতে হবে।',
                        'status' => DiaryStatus::Published, 'published_at' => $day->copy()->setTime(14, 0),
                    ]);
                }
            }
        }

        $this->remarksAndAcknowledgements($created);
    }

    /**
     * @param  Collection<int, DiaryEntry>  $entries
     */
    protected function remarksAndAcknowledgements($entries): void
    {
        $published = $entries->filter(fn (DiaryEntry $entry): bool => $entry->isPublished());

        foreach ($published->groupBy('section_id') as $sectionEntries) {
            $students = Student::query()->where('section_id', $sectionEntries->first()->section_id)->where('class_id', $sectionEntries->first()->class_id)->orderBy('roll')->get();

            foreach ($students as $studentIndex => $student) {
                foreach ($sectionEntries as $entryIndex => $entry) {
                    $roll = crc32("{$student->id}:{$entry->id}") % 100;

                    // Most guardians have seen older entries; today's are mostly still unseen.
                    $isToday = $entry->entry_date->isToday();

                    if (($isToday && $roll < 70) || (! $isToday && $roll < 12)) {
                        continue;
                    }

                    DiaryAcknowledgement::create(['diary_entry_id' => $entry->id, 'student_id' => $student->id, 'seen_at' => $entry->published_at?->copy()->addHours(3), 'acknowledged_at' => $roll > 55 ? $entry->published_at?->copy()->addHours(4) : null]);
                }
            }

            $entry = $sectionEntries->first(fn (DiaryEntry $candidate): bool => $candidate->subject_id !== null);

            if ($entry && $students->isNotEmpty()) {
                $entry->remarks()->create(['student_id' => $students->first()->id, 'teacher_id' => $entry->teacher_id, 'kind' => RemarkKind::Praise, 'text' => 'আজ খুব সুন্দরভাবে পড়া শুনিয়েছে।']);

                if ($students->count() > 1) {
                    $entry->remarks()->create(['student_id' => $students->last()->id, 'teacher_id' => $entry->teacher_id, 'kind' => RemarkKind::Concern, 'text' => 'বাড়ির কাজ আনেনি, একটু যত্ন নিতে হবে।']);
                }
            }
        }
    }
}
