{{-- The student's photo, or the dashed "photo" placeholder the design uses. Fills its (sized, clipped) parent. --}}
@if ($student->photo_url)
    <img src="{{ $student->photo_url }}" alt="" style="width: 100%; height: 100%; object-fit: cover; display: block;">
@else
    <span style="width: 100%; height: 100%; display: grid; place-content: center; justify-items: center; gap: 2px; text-align: center; color: inherit; opacity: .7; border: 1px dashed currentColor; border-radius: {{ $radius ?? 16 }}px; line-height: 1.2;">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
        <span style="font-size: {{ $caption ?? 13 }}px;">{{ __('student-portal::messages.photo') }}</span>
    </span>
@endif
