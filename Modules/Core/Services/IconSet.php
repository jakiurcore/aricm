<?php

namespace Modules\Core\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\File;

/**
 * The shared duotone icon set (Modules/Core/resources/icons/*.svg): the Blade component inlines these files and the
 * admin icon picker imports the very same ones. Source files are read once per request.
 */
#[Singleton]
class IconSet
{
    /**
     * Legacy emoji/glyph values (what the site stored before icon keys existed) => icon key.
     *
     * @var array<string, string>
     */
    public const array LEGACY = [
        '📖' => 'book', '📓' => 'book', '📚' => 'book',
        '🏫' => 'school',
        '🎖' => 'medal', '🏅' => 'medal',
        '🔤' => 'phonetics',
        '🧮' => 'abacus',
        '🛡' => 'shield',
        '🧠' => 'brain',
        '☎' => 'phone', '📞' => 'phone',
        '★' => 'star', '⭐' => 'star',
        '☾' => 'moon', '☀' => 'sun',
        '→' => 'arrow',
        '✓' => 'check', '✔' => 'check',
        '📍' => 'pin',
        '🔔' => 'bell', '📢' => 'bell',
        '🕐' => 'clock',
        '📷' => 'camera',
        '👤' => 'users', '👥' => 'users',
        '✎' => 'pencil', '✏' => 'pencil', '📝' => 'pencil',
        '🗓' => 'calendar', '📅' => 'calendar',
        '▶' => 'play',
        '✉' => 'mail',
        '🏠' => 'home', '⌂' => 'home',
    ];

    /**
     * @var array<string, string|null>
     */
    protected array $sources = [];

    /**
     * @var list<string>|null
     */
    protected ?array $keys = null;

    /**
     * Every available icon key, alphabetically.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return $this->keys ??= collect(File::glob($this->directory().'/*.svg'))
            ->map(fn (string $path): string => basename($path, '.svg'))
            ->sort()
            ->values()
            ->all();
    }

    public function has(string $key): bool
    {
        return in_array($key, $this->keys(), true);
    }

    /**
     * The key for a stored value: a known key passes through, a legacy emoji is mapped, anything else is null.
     */
    public function keyFor(?string $value): ?string
    {
        $value = trim(str_replace("\u{FE0F}", '', (string) $value));

        if ($value === '') {
            return null;
        }

        if ($this->has($value)) {
            return $value;
        }

        return self::LEGACY[$value] ?? null;
    }

    /**
     * The inline SVG of an icon (decorative: aria-hidden), or null for an unknown key.
     */
    public function svg(string $key, int|string $size = 24): ?string
    {
        $source = $this->source($key);

        if ($source === null) {
            return null;
        }

        $size = (int) $size;

        return preg_replace('/^<svg /', '<svg class="ico" width="'.$size.'" height="'.$size.'" aria-hidden="true" role="presentation" focusable="false" ', $source, 1);
    }

    protected function source(string $key): ?string
    {
        if (array_key_exists($key, $this->sources)) {
            return $this->sources[$key];
        }

        $path = $this->directory().'/'.$key.'.svg';

        return $this->sources[$key] = $this->has($key) && File::exists($path) ? trim(File::get($path)) : null;
    }

    protected function directory(): string
    {
        return dirname(__DIR__).'/resources/icons';
    }
}
