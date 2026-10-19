{{-- The empty photo placeholder of the design: a dashed ring, an image glyph and a caption. --}}
<div class="relative size-full overflow-hidden rounded-full bg-[rgba(127,127,127,.08)] text-inherit" aria-hidden="true">
    <div class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 p-3 text-center">
        <svg class="opacity-45" width="{{ $glyph ?? 28 }}" height="{{ $glyph ?? 28 }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
        <span class="max-w-[90%] text-[13px] font-medium leading-[1.3] tracking-[.01em] opacity-75">{{ __('teacher-panel::messages.photo') }}</span>
    </div>
    <div class="pointer-events-none absolute inset-0 rounded-full border-[1.5px] border-dashed border-current opacity-35"></div>
</div>
