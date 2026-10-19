@props(['exams', 'classMap', 'compact' => false])

<form method="POST" action="{{ route('results.public.search') }}" class="grid gap-4 {{ $compact ? 'sm:grid-cols-2 lg:grid-cols-5' : 'sm:grid-cols-2' }}"
      x-data="{ exam: '{{ old('exam_id') }}', classes: @js($classMap), classId: '{{ old('class_id') }}' }">
    @csrf

    <div>
        <label for="exam_id" class="mb-1 block text-sm font-medium">{{ __('results::messages.public.exam') }}</label>
        <select id="exam_id" name="exam_id" x-model="exam" required class="w-full rounded-md border border-line-strong bg-surface px-3">
            <option value="">—</option>
            @foreach ($exams as $exam)
                <option value="{{ $exam->id }}">{{ $exam->displayName() }}</option>
            @endforeach
        </select>
        @error('exam_id')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="class_id" class="mb-1 block text-sm font-medium">{{ __('results::messages.public.class') }}</label>
        <select id="class_id" name="class_id" x-model="classId" required class="w-full rounded-md border border-line-strong bg-surface px-3">
            <option value="">—</option>
            <template x-for="schoolClass in (classes[exam] || [])" :key="schoolClass.id">
                <option :value="schoolClass.id" x-text="schoolClass.name"></option>
            </template>
        </select>
        @error('class_id')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="identifier" class="mb-1 block text-sm font-medium">{{ __('results::messages.public.identifier') }}</label>
        <input id="identifier" name="identifier" value="{{ old('identifier') }}" required autocomplete="off" class="w-full rounded-md border border-line-strong bg-surface px-3">
        @error('identifier')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="date_of_birth" class="mb-1 block text-sm font-medium">{{ __('results::messages.public.dob') }}</label>
        <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" required class="w-full rounded-md border border-line-strong bg-surface px-3">
        @error('date_of_birth')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
    </div>

    <div class="{{ $compact ? '' : 'sm:col-span-2' }} flex items-end">
        <button type="submit" class="min-h-11 w-full rounded-md bg-brand px-6 font-semibold text-white">{{ __('results::messages.public.submit') }}</button>
    </div>
</form>
