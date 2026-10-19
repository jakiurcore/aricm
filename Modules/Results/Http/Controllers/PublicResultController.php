<?php

namespace Modules\Results\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Exams\Models\Exam;
use Modules\Results\Http\Requests\PublicSearchRequest;
use Modules\Results\Services\ResultDirectory;
use Modules\Results\Services\ResultSearch;

class PublicResultController
{
    public function index(): View
    {
        return view('results::public.search', $this->formData());
    }

    public function search(PublicSearchRequest $request, ResultSearch $search, ResultDirectory $results): View
    {
        $exam = Exam::query()->where('result_published', true)->find($request->integer('exam_id'));
        $student = $exam ? $search->find($exam, $request->integer('class_id'), $request->string('identifier')->toString(), $request->string('date_of_birth')->toString()) : null;
        $result = $student ? $results->publishedResult($exam, $student) : null;

        // Same answer for "no such student", "wrong date of birth" and "not published": nothing to learn from it.
        if (! $result) {
            return view('results::public.search', [...$this->formData(), 'notFound' => true]);
        }

        $decision = $results->eligibility($exam, $student);

        if (! $decision->allowed) {
            return view('results::public.search', [...$this->formData(), 'blocked' => $decision->reason]);
        }

        return view('results::public.show', ['result' => $result]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        $exams = Exam::query()->with('classes')->where('result_published', true)->orderByDesc('start_date')->orderByDesc('id')->get();

        return [
            'exams' => $exams,
            'classMap' => $exams->mapWithKeys(fn (Exam $exam): array => [$exam->id => $exam->classes->map(fn ($class): array => ['id' => $class->id, 'name' => $class->displayName()])->values()]),
            'notFound' => false,
            'blocked' => null,
        ];
    }
}
