<?php

namespace Modules\Core\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Institute details every printed document shows, read from the settings.
 */
final class Branding
{
    /**
     * @return array{name_bn: string, name_en: string, name_ar: string, address: string, phones: string, logo: string, principal: string, signature: string|null, established: string, logo_gray: string}
     */
    public static function forPdf(): array
    {
        $locale = app()->getLocale();
        $logo = setting('logo');

        return [
            'name_bn' => (string) setting('institute_name_bn'),
            'name_en' => (string) setting('institute_name_en'),
            'name_ar' => (string) setting('institute_name_ar'),
            'address' => (string) (setting("address_{$locale}") ?: setting('address_bn')),
            'phones' => implode(', ', array_map(fn (string $phone): string => bn_digits($phone), (array) setting('phones', []))),
            'logo' => $logo && Storage::disk('public')->exists($logo) ? Storage::disk('public')->path($logo) : base_path('Modules/Core/resources/images/logo.jpeg'),
            'logo_gray' => $logo && Storage::disk('public')->exists($logo) ? Storage::disk('public')->path($logo) : base_path('Modules/Core/resources/images/logo-gray.jpeg'),
            'principal' => (string) (setting("principal_name_{$locale}") ?: setting('principal_name_bn')),
            'signature' => ($signature = setting('principal_signature')) && Storage::disk('public')->exists($signature) ? Storage::disk('public')->path($signature) : null,
            'established' => bn_digits((string) setting('established_year')),
        ];
    }

    /**
     * The grey "image" glyph shown in an empty photo frame (SVG, so it needs no GD).
     */
    public static function photoPlaceholder(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"><rect x="6" y="8" width="36" height="32" rx="5" fill="none" stroke="#8A939E" stroke-width="3"/><circle cx="17" cy="19" r="3.5" fill="#8A939E"/><path d="M8 36 L19 26 L27 33 L33 27 L41 35" fill="none" stroke="#8A939E" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"/></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * A square (0.4 mm larger than the logo on every side, 11.8 mm for an 11 mm logo) with a round hole, laid over a square
     * logo so only the circle shows. mPDF cannot clip an image to a circle, so the corners are painted over: the two upper
     * corners in `$top`, the two lower ones in `$bottom`, to match a background that shades from one to the other.
     */
    public static function discMask(string $top, string $bottom): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="118" height="118" viewBox="0 0 118 118">'
            .'<path fill="'.$top.'" d="M0 0H118V59H113.5A54.5 54.5 0 0 0 4.5 59H0Z"/>'
            .'<path fill="'.$bottom.'" d="M0 59H4.5A54.5 54.5 0 0 0 113.5 59H118V118H0Z"/></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * One rounded end of a pill (a half disc as SVG). mPDF cannot round an auto-width box, so a pill is built as
     * cap + text cell + cap inside a shrink-to-fit table.
     */
    public static function pillCap(string $color, bool $left): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="52.5" height="100" viewBox="0 0 5.25 10"><path fill="'.$color.'" d="'.($left ? 'M5.25 0H5A5 5 0 0 0 5 10H5.25Z' : 'M0 0H0.25A5 5 0 0 1 0.25 10H0Z').'"/></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
