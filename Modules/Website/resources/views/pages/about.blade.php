@extends('core::layouts.public')

@section('title', site_content()->text('about.title'))

@section('content')
    @php($identity = app(\Modules\Core\Services\PublicSite::class)->identity())
    <div style="max-width: 1040px; margin: 0 auto; padding: 40px 16px 0; display: grid; gap: 40px;">
        <div style="display: grid; gap: 10px;">
            <span style="color: var(--green-700); font-weight: 700;">{{ site_content()->text('about.kicker') }}</span>
            <h1 class="pub-h1">{{ $identity['name'] }}</h1>
            <p style="margin: 0; font-size: 18px; color: var(--muted); max-width: 70ch; text-wrap: pretty;">{{ site_content()->text('about.body', ['year' => $identity['established'], 'name' => $identity['name']]) }}</p>
        </div>
        <div style="aspect-ratio: 21 / 9; border-radius: 24px; overflow: hidden; background: var(--surface-2);">
            <img src="{{ site_content()->image('campus_image') }}" alt="{{ site_content()->text('about.campus_alt') }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; display: block;">
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap: 16px;">
            @foreach (site_content()->items('about_values') as $value)
                <div class="pub-card" style="display: grid; gap: 6px;">
                    <h3 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: 22px;">{{ $value->title() }}</h3>
                    <p style="margin: 0; color: var(--muted);">{{ $value->body() }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endsection
