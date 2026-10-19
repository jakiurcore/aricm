<?php

namespace Modules\Website\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Exams\Enums\ExamStatus;
use Modules\Exams\Models\Exam;
use Modules\Exams\Services\ExamDirectory;
use Modules\Website\Services\HomeContent;
use Modules\Website\Services\HomepageComposer;

class PageController
{
    public function home(HomepageComposer $composer): View
    {
        return view('website::home', ['sections' => $composer->sections()]);
    }

    public function about(HomeContent $content): View
    {
        return view('website::pages.about', ['content' => $content]);
    }

    public function academics(AcademicDirectory $academic): View
    {
        return view('website::pages.academics', ['classes' => $academic->classes()]);
    }

    public function contact(HomeContent $content): View
    {
        return view('website::pages.contact', ['map' => $content->map()]);
    }

    /**
     * Published exam routines: pick an exam, then a class. Only exams that are no longer drafts are listed.
     */
    public function routine(Request $request, ExamDirectory $directory): View
    {
        $exams = Exam::query()->with('classes')->where('status', '!=', ExamStatus::Draft->value)->whereHas('routines')->orderByDesc('start_date')->get();
        $exam = $exams->firstWhere('id', $request->integer('exam')) ?? $exams->first();
        $class = $exam?->classes->firstWhere('id', $request->integer('class')) ?? $exam?->classes->first();

        return view('website::pages.routine', [
            'exams' => $exams, 'exam' => $exam, 'class' => $class,
            'routine' => $exam && $class ? $directory->routineFor($exam, $class->id) : collect(),
        ]);
    }
}
