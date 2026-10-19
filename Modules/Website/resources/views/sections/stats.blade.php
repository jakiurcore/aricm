<section style="margin-top: 64px; background: linear-gradient(135deg, var(--green-700), var(--green-900)); color: #fff;">
    <div style="max-width: 1240px; margin: 0 auto; padding: 40px 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr)); gap: 24px;">
        @foreach ($stats as $stat)
            <div style="display: grid;">
                <span style="font-family: var(--f-head); font-size: clamp(40px, 6vw, 54px); line-height: 1.45; color: var(--yellow-400);">{{ $stat['value'] }}</span>
                <span style="font-size: 17px; font-weight: 500;">{{ $stat['label'] }}</span>
            </div>
        @endforeach
    </div>
</section>
