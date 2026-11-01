<?php

use Illuminate\Support\Facades\Blade;
use Modules\Core\Services\IconSet;

it('renders the inline svg of an icon key as a decorative element', function () {
    $html = Blade::render('<x-core::icon name="brain" :size="32" class="mine" />');

    expect($html)->toContain('<svg')->toContain('data-icon="brain"')->toContain('aria-hidden="true"')->toContain('role="presentation"')
        ->toContain('width="32"')->toContain('ico-hemi')->toContain('mine')->not->toContain('pub-ico ');
});

it('wraps a badge and adds the spark ring only when asked', function () {
    expect(Blade::render('<x-core::icon name="star" badge />'))->toContain('pub-ico')->not->toContain('pub-ico-ring')
        ->and(Blade::render('<x-core::icon name="star" badge ring />'))->toContain('pub-ico')->toContain('pub-ico-ring');
});

it('accepts a legacy emoji and maps it to the icon key', function () {
    expect(Blade::render('<x-core::icon name="🧮" />'))->toContain('data-icon="abacus"')
        ->and(core_icon_key('🧠'))->toBe('brain')->and(core_icon_key('☎'))->toBe('phone')->and(core_icon_key('→'))->toBe('arrow')
        ->and(core_icon_key('★'))->toBe('star')->and(core_icon_key('☾'))->toBe('moon')->and(core_icon_key('🛡️'))->toBe('shield')
        ->and(core_icon_key('book'))->toBe('book')->and(core_icon_key('🦄'))->toBeNull()->and(core_icon_key(null))->toBeNull()->and(core_icon_key(''))->toBeNull();
});

it('falls back to the raw text for an unknown value and renders nothing for an empty one', function () {
    $html = Blade::render('<x-core::icon name="🦄" />');

    expect($html)->toContain('🦄')->toContain('aria-hidden="true"')->not->toContain('<svg')
        ->and(trim(Blade::render('<x-core::icon name="" />')))->toBe('');
});

it('keeps every svg file well formed with a 24 box and no scripts or external assets', function () {
    $icons = app(IconSet::class);

    expect($icons->keys())->toContain('brain', 'abacus', 'book', 'school', 'medal', 'shield', 'phonetics', 'pin', 'phone', 'check', 'star', 'bell', 'moon', 'sun', 'arrow', 'clock', 'camera', 'users', 'pencil', 'calendar', 'play');

    foreach ($icons->keys() as $key) {
        $source = file_get_contents(base_path("Modules/Core/resources/icons/{$key}.svg"));
        $xml = simplexml_load_string($source);

        expect($xml)->not->toBeFalse($key)
            ->and((string) $xml['viewBox'])->toBe('0 0 24 24')
            ->and($source)->not->toContain('<script')->not->toContain('href=')->not->toContain('<image');
    }
});

it('draws the hero brain illustration as a decorative svg', function () {
    $html = Blade::render('<x-core::brain-art class="w-full" />');

    expect($html)->toContain('brain-art')->toContain('aria-hidden="true"')->toContain('ba-n')->toContain('ba-ring');
});
