@if ($notices->isNotEmpty())
    <div class="pub-wrap" style="margin-top: 64px;">
        <section style="display: grid; gap: 20px;">
            <div style="display: flex; flex-wrap: wrap; align-items: end; justify-content: space-between; gap: 12px;">
                <div style="display: grid; gap: 4px;">
                    <span class="pub-kicker">{{ __('notices::messages.kicker') }}</span>
                    <h2 class="pub-h2">{{ __('notices::messages.latest') }}</h2>
                </div>
                <a href="{{ route('notices.public.index') }}" class="pub-btn-outline">{{ __('notices::messages.view_all') }} <x-core::icon name="arrow" size="18" /></a>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 12px;">
                @foreach ($notices->take(5) as $notice)
                    <x-notices::notice-row :notice="$notice" />
                @endforeach
            </div>
        </section>
    </div>
@endif
