@props(['exams', 'classMap'])

@if ($exams->isNotEmpty())
    <section id="result" class="mx-auto max-w-page px-4 py-10">
        <div class="rounded-2xl border border-line bg-surface p-6 shadow-md">
            <h2 class="mb-4 font-head text-2xl text-heading">{{ __('results::messages.public.title') }}</h2>
            @include('results::partials.search-form', ['exams' => $exams, 'classMap' => $classMap, 'compact' => true])
        </div>
    </section>
@endif
