<div style="max-width: 1240px; margin: 0 auto; padding: 64px 16px 0;">
    <section style="border-radius: 24px; overflow: hidden; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-md); display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr));">
        <div style="padding: clamp(20px, 3vw, 32px); display: grid; gap: 16px; align-content: start;">
            <h2 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: clamp(26px, 3.5vw, 34px); line-height: 1.3;">{{ site_content()->text('contact.title') }}</h2>
            <div style="display: grid; gap: 2px;"><span style="font-size: 14px; font-weight: 700; color: var(--muted);">{{ site_content()->text('contact.address') }}</span><span style="font-size: 17px;">{{ $address }}</span></div>
            <div style="display: grid; gap: 8px;">
                <span style="font-size: 14px; font-weight: 700; color: var(--muted);">{{ site_content()->text('contact.phone') }}</span>
                @foreach ($phones as $phone)
                    <a href="tel:{{ $phone }}" style="display: flex; align-items: center; gap: 12px; min-height: 52px; padding: 0 16px; border-radius: 14px; background: var(--green-50); color: var(--green-700); font-weight: 700; font-size: 18px; text-decoration: none;"><x-core::icon name="phone" size="18" badge style="--ico-bg: var(--green-700); color: #fff; margin: 3px;" />{{ bn_digits($phone) }}</a>
                @endforeach
                @if ($email)
                    <a href="mailto:{{ $email }}" class="pub-link" style="font-weight: 600;">{{ $email }}</a>
                @endif
            </div>
            <span style="color: var(--muted); font-size: 15px;">{{ app(\Modules\Core\Services\PublicSite::class)->identity()['office_hours'] ?: site_content()->text('contact.hours') }}</span>
        </div>
        <iframe src="{{ $map['embed'] }}" title="{{ site_content()->text('contact.map') }}" loading="lazy" style="width: 100%; min-height: 320px; height: 100%; border: 0; background: var(--surface-2);"></iframe>
    </section>
</div>
