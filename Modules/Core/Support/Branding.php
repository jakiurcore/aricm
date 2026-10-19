<?php

namespace Modules\Core\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Institute details every printed document shows, read from the settings.
 */
final class Branding
{
    /**
     * @return array{name_bn: string, name_en: string, name_ar: string, address: string, phones: string, logo: string, principal: string, signature: string|null, established: string}
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
            'principal' => (string) (setting("principal_name_{$locale}") ?: setting('principal_name_bn')),
            'signature' => ($signature = setting('principal_signature')) && Storage::disk('public')->exists($signature) ? Storage::disk('public')->path($signature) : null,
            'established' => bn_digits((string) setting('established_year')),
        ];
    }
}
