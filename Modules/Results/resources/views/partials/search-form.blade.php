@props(['exams', 'classMap'])

<form method="POST" action="{{ route('results.public.search') }}" class="pub-card" style="padding: 20px; box-shadow: var(--shadow-sm); display: grid; gap: 12px;"
      x-data="{
          exam: '{{ old('exam_id', request('exam_id')) }}',
          classes: @js($classMap),
          classId: '{{ old('class_id') }}',
          fit() {
              const options = this.classes[this.exam] || [];
              if (! options.some((item) => String(item.id) === String(this.classId))) { this.classId = options.length ? String(options[0].id) : ''; }
          },
          init() {
              if (! this.exam || ! this.classes[this.exam]) { this.exam = String(Object.keys(this.classes)[0] ?? ''); }
              this.$nextTick(() => this.fit());
              this.$watch('exam', () => this.$nextTick(() => this.fit()));
          },
      }">
    @csrf

    <label class="pub-field" for="exam_id">{{ __('results::messages.public.exam') }}
        <select id="exam_id" name="exam_id" x-model="exam" required class="pub-input">
            @foreach ($exams as $exam)
                <option value="{{ $exam->id }}">{{ $exam->displayName() }}</option>
            @endforeach
        </select>
        @error('exam_id')<span class="pub-err">{{ $message }}</span>@enderror
    </label>

    <label class="pub-field" for="class_id">{{ __('results::messages.public.class') }}
        <select id="class_id" name="class_id" x-model="classId" required class="pub-input">
            <template x-for="schoolClass in (classes[exam] || [])" :key="schoolClass.id">
                <option :value="schoolClass.id" x-text="schoolClass.name" :selected="String(schoolClass.id) === String(classId)"></option>
            </template>
        </select>
        @error('class_id')<span class="pub-err">{{ $message }}</span>@enderror
    </label>

    <label class="pub-field" for="identifier">{{ __('results::messages.public.identifier') }}
        <input id="identifier" name="identifier" inputmode="numeric" value="{{ old('identifier', request('identifier')) }}" placeholder="{{ __('results::messages.public.identifier_placeholder') }}" required autocomplete="off" class="pub-input">
        @error('identifier')<span class="pub-err">{{ $message }}</span>@enderror
    </label>

    <label class="pub-field" for="date_of_birth">{{ __('results::messages.public.dob') }}
        <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" required class="pub-input">
        @error('date_of_birth')<span class="pub-err">{{ $message }}</span>@enderror
    </label>

    <button type="submit" style="height: 52px; border: 0; border-radius: 999px; background: var(--green-700); color: #fff; font-weight: 700; font-size: 17px; cursor: pointer;">{{ __('results::messages.public.submit') }}</button>
    <span style="font-size: 13px; color: var(--muted);">{{ __('results::messages.public.hint') }}</span>
</form>
