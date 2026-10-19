<?php

namespace Modules\Students\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Http\Controllers\Concerns\ServesPdf;
use Modules\Students\Models\Student;
use Modules\Students\Services\IdCardPdf;
use Modules\Students\Services\StudentDirectory;

class IdCardController
{
    use ServesPdf;

    /**
     * ID cards for a class/section, or for chosen students (ids[]). Capped so one request cannot exhaust memory.
     */
    public function index(Request $request, StudentDirectory $directory, IdCardPdf $cards): Response
    {
        Gate::authorize('printIdCards', Student::class);

        $data = $request->validate([
            'class_id' => ['nullable', 'integer'], 'section_id' => ['nullable', 'integer'], 'session_id' => ['nullable', 'integer'],
            'ids' => ['nullable', 'array', 'max:200'], 'ids.*' => ['integer'],
        ]);

        abort_if(empty($data['ids']) && empty($data['class_id']), 422, __('students::messages.id_card_needs_filter'));

        $students = $directory->query(['class_id' => $data['class_id'] ?? null, 'section_id' => $data['section_id'] ?? null, 'session_id' => $data['session_id'] ?? null, 'status' => 'active'])
            ->when(! empty($data['ids']), fn ($query) => $query->whereIn('id', $data['ids']))
            ->orderBy('class_id')->orderByRaw('roll is null')->orderBy('roll')
            ->limit(200)->get();

        abort_if($students->isEmpty(), 404);

        return $this->pdf($cards->render($students), 'id-cards.pdf');
    }

    public function show(Student $student, IdCardPdf $cards): Response
    {
        Gate::authorize('printIdCards', Student::class);

        return $this->pdf($cards->render(collect([$student])), "id-card-{$student->student_id}.pdf");
    }
}
