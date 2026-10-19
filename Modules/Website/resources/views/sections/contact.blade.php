<div style="max-width: 1240px; margin: 0 auto; padding: 64px 16px 0;">
    <section style="border-radius: 24px; overflow: hidden; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-md); display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr));">
        <div style="padding: clamp(20px, 3vw, 32px); display: grid; gap: 16px; align-content: start;">
            <h2 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: clamp(26px, 3.5vw, 34px); line-height: 1.3;">{{ __('website::messages.contact.title') }}</h2>
            <div style="display: grid; gap: 2px;"><span style="font-size: 14px; font-weight: 700; color: var(--muted);">{{ __('website::messages.contact.address') }}</span><span style="font-size: 17px;">{{ $address }}</span></div>
            <div style="display: grid; gap: 8px;">
                <span style="font-size: 14px; font-weight: 700; color: var(--muted);">{{ __('website::messages.contact.phone') }}</span>
                @foreach ($phones as $phone)
                    <a href="tel:{{ $phone }}" style="display: flex; align-items: center; gap: 12px; min-height: 52px; padding: 0 16px; border-radius: 14px; background: var(--green-50); color: var(--green-700); font-weight: 700; font-size: 18px; text-decoration: none;"><span style="width: 32px; height: 32px; border-radius: 50%; background: var(--green-700); color: #fff; display: grid; place-items: center; font-size: 15px;">☎</span>{{ bn_digits($phone) }}</a>
                @endforeach
                @if ($email)
                    <a href="mailto:{{ $email }}" class="pub-link" style="font-weight: 600;">{{ $email }}</a>
                @endif
            </div>
            <span style="color: var(--muted); font-size: 15px;">{{ __('website::messages.contact.hours') }}</span>
        </div>
        <iframe src="{{ $map['embed'] }}" title="{{ __('website::messages.contact.map') }}" loading="lazy" style="width: 100%; min-height: 320px; height: 100%; border: 0; background: var(--surface-2);"></iframe>
    </section>
</div>
