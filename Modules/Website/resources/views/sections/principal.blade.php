@php
    $identity = app(\Modules\Core\Services\PublicSite::class)->identity();
    $locale = app()->getLocale();
    $principalName = (string) (setting("principal_name_{$locale}") ?: setting('principal_name_bn'));

    if ($principalName === '' && site_content()->text('principal.default_name') !== (string) __('website::messages.principal.default_name')) {
        $principalName = site_content()->text('principal.default_name');
    }
@endphp
<div class="pub-wrap" style="margin-top: 64px;">
    <section style="border-radius: 28px; background: var(--navy-50); padding: clamp(20px, 4vw, 40px); display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); gap: 32px; align-items: center;">
        <div style="width: 100%; max-width: 320px; aspect-ratio: 4 / 5; border-radius: 24px; overflow: hidden; border: 6px solid var(--surface); box-shadow: var(--shadow-md); background: var(--surface);">
            <img src="{{ site_content()->image('principal_photo') }}" alt="{{ site_content()->text('principal.photo_alt') }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 18px;">
        </div>
        <div style="display: grid; gap: 14px; min-width: 0; grid-column: span 2;">
            <span style="color: var(--navy-800); font-weight: 700; font-size: 14px;">{{ site_content()->text('principal.kicker') }}</span>
            <blockquote style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: clamp(21px, 2.8vw, 28px); line-height: 1.65; text-wrap: pretty; white-space: pre-line;">“{{ $content->principalMessage() }}”</blockquote>
            <span style="display: grid; line-height: 1.4;">@if ($principalName !== '')<span style="font-weight: 700; font-size: 18px;">{{ $principalName }}</span>@endif<span style="color: var(--muted);">{{ $identity['principal_title'] ?: site_content()->text('principal.role', ['name' => $identity['name']]) }}</span></span>
        </div>
    </section>
</div>
