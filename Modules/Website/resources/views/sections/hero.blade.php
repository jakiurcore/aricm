@php
    $identity = app(\Modules\Core\Services\PublicSite::class)->identity();
    $phone = $identity['phones'][0] ?? null;
    $benefits = site_content()->items('programs');
    $hasAdmission = Route::has('admission.public');

    $admission = $hasAdmission ? app(\Modules\Admission\Services\AdmissionSettings::class) : null;
    $bannerOn = $admission?->bannerEnabled() ?? false;
    $admissionTitle = $bannerOn ? site_content()->text('hero.admission_title') : '';
    $freeNote = $bannerOn ? $admission->text('free_class_note') : '';
    $classStart = $bannerOn ? $admission->get('class_start') : null;
    $showFree = filled($freeNote) || filled($classStart);

    $founderName = site_content()->text('hero.founder_name');
    $founderTitle = site_content()->text('hero.founder_title');
    $showFounder = filled($founderName) || filled($founderTitle);

    $arabic = site_content()->text('hero.arabic');
    $pillTints = ['green', 'purple', 'yellow', 'navy'];
@endphp
<section class="pub-hero-pattern pub-hero" aria-labelledby="hero-title">
    <div class="pub-hero-grid">
        <div class="pub-hero-text">
            @if (filled($arabic))
                <span class="pub-hero-arabic" dir="rtl" lang="ar">{{ $arabic }}</span>
            @endif
            <h1 id="hero-title" class="pub-hero-title">{{ $identity['name'] }}</h1>

            @if (filled($identity['program']))
                <a @if ($benefits->isNotEmpty()) href="#abacus" @endif class="pub-hero-ribbon">
                    <x-core::icon name="brain" size="20" badge ring />
                    <span>{{ $identity['program'] }}</span>
                </a>
            @endif

            @if (filled($identity['address']))
                <p class="pub-hero-location"><x-core::icon name="pin" size="20" /><span>{{ $identity['address'] }}</span></p>
            @endif

            <p class="pub-hero-kicker">{{ site_content()->text('hero.tag1') }}</p>

            @if (filled($admissionTitle) || $showFree)
                <div class="pub-hero-promo">
                    @if (filled($admissionTitle))
                        <a href="{{ route('admission.public') }}" class="pub-hero-banner pub-shine">
                            <x-core::icon name="bell" size="26" badge />
                            <span class="pub-hero-banner-text">{{ $admissionTitle }}</span>
                            <x-core::icon name="star" size="22" class="pub-hero-banner-star" />
                        </a>
                    @endif
                    @if ($showFree)
                        <div class="pub-hero-free">
                            @if (filled($freeNote))
                                <strong>{{ $freeNote }}</strong>
                            @endif
                            @if (filled($classStart))
                                <span><x-core::icon name="calendar" size="18" /> {{ __('admission::messages.class_start') }}: {{ bn_date($classStart) }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if (filled(site_content()->text('hero.body')))
                <p class="pub-hero-body">{{ site_content()->text('hero.body') }}</p>
            @endif

            <div class="pub-hero-cta">
                @if ($hasAdmission)
                    <a href="{{ route('admission.public') }}" class="pub-hero-apply">{{ site_content()->text('hero.apply') }} <x-core::icon name="arrow" size="20" /></a>
                @endif
                @if ($phone)
                    <a href="tel:{{ $phone }}" class="pub-hero-callbtn"><x-core::icon name="phone" size="20" /> {{ site_content()->text('hero.call') }}</a>
                @endif
            </div>
            <span class="pub-hero-invite">{{ site_content()->text('hero.tag2') }}</span>
        </div>

        @if (filled($identity['tagline']))
            <p class="pub-hero-slogan">{{ $identity['tagline'] }}</p>
        @endif

        <div class="pub-hero-media">
            <div class="pub-hero-photo">
                <img src="{{ site_content()->image('hero_image') }}" alt="{{ site_content()->text('hero.photo_alt') }}" width="1000" height="700" fetchpriority="high">
                <span class="pub-hero-chip pub-hero-chip-est"><x-core::icon name="star" size="16" /> {{ site_content()->text('hero.badge_est', ['year' => $identity['established']]) }}</span>
                <span class="pub-hero-chip pub-hero-chip-hifz"><x-core::icon name="book" size="18" /> {{ site_content()->text('hero.badge_hifz') }}</span>
                <span class="pub-hero-chip pub-hero-chip-cadet"><x-core::icon name="shield" size="16" /> {{ site_content()->text('hero.badge_cadet') }}</span>
            </div>

            @if ($benefits->isNotEmpty())
                <div class="pub-hero-brain">
                    <div class="pub-hero-brain-head">
                        <span class="pub-hero-brain-tag">{{ site_content()->text('hero.abacus_tag') }}</span>
                        <span class="pub-hero-brain-sub">{{ site_content()->text('hero.abacus_sub') }}</span>
                    </div>
                    <div class="pub-hero-brain-body">
                        <x-core::brain-art class="pub-hero-brain-art" />
                        <ul class="pub-hero-pills">
                            @foreach ($benefits->take(4) as $index => $benefit)
                                <li class="pub-hero-pill pub-hero-pill-{{ $pillTints[$index] ?? 'green' }}"><span class="pub-hero-pill-check"><x-core::icon name="check" size="16" /></span><span>{{ $benefit->title() }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        @if ($showFounder || $phone)
            <div class="pub-hero-strip">
                @if ($showFounder)
                    <div class="pub-hero-founder">
                        <img src="{{ site_content()->image('founder_photo') }}" alt="{{ $founderName }}" width="76" height="76" loading="lazy">
                        <div>
                            @if (filled(site_content()->text('hero.founder_label')))
                                <span class="pub-hero-founder-label">{{ site_content()->text('hero.founder_label') }}</span>
                            @endif
                            @if (filled($founderName))
                                <strong>{{ $founderName }}</strong>
                            @endif
                            @if (filled($founderTitle))
                                <span class="pub-hero-founder-title">{{ $founderTitle }}</span>
                            @endif
                        </div>
                    </div>
                @endif
                @if ($phone)
                    <a href="tel:{{ $phone }}" class="pub-hero-callpill pub-shine" aria-label="{{ site_content()->text('hero.call') }} {{ bn_digits($phone) }}">
                        <x-core::icon name="phone" size="30" badge />
                        <span><small>{{ site_content()->text('hero.call') }}</small><strong>{{ bn_digits($phone) }}</strong></span>
                    </a>
                @endif
            </div>
        @endif
    </div>
</section>
@if ($phone)
    <a href="tel:{{ $phone }}" class="pub-callbar" aria-label="{{ site_content()->text('hero.call') }} {{ bn_digits($phone) }}">
        <x-core::icon name="phone" size="22" />
        <span>{{ site_content()->text('hero.call') }}</span>
        <strong>{{ bn_digits($phone) }}</strong>
    </a>
@endif
