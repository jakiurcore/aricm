<?php

namespace Modules\Website\Services;

/**
 * Every editable scalar text and image of the public site. The admin form is generated from this list, so making a new
 * text editable is one line in {@see self::definitions()}. The default of a text is the lang string under the same key,
 * so nothing breaks while nothing has been edited.
 *
 * @phpstan-type SchemaEntry array{key: string, page: string, group: string, type: string, label: string, default: string, default_lang: string|null}
 */
class ContentSchema
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $entries = null;

    /**
     * @return list<array{key: string, page: string, group: string, type: string, label: string, default: string, default_lang: string|null}>
     */
    public static function all(): array
    {
        return array_values(self::entries());
    }

    /**
     * @return array{key: string, page: string, group: string, type: string, label: string, default: string, default_lang: string|null}|null
     */
    public static function find(string $key): ?array
    {
        return self::entries()[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::entries());
    }

    /**
     * @return array<string, array{key: string, page: string, group: string, type: string, label: string, default: string, default_lang: string|null}>
     */
    private static function entries(): array
    {
        if (self::$entries !== null) {
            return self::$entries;
        }

        $entries = [];

        foreach (self::definitions() as $page => $groups) {
            foreach ($groups as $group => $fields) {
                foreach ($fields as $key => $definition) {
                    [$type, $default] = is_array($definition) ? $definition : [$definition, null];
                    $isAdmission = str_starts_with($key, 'admission.');

                    $entries[$key] = [
                        'key' => $key,
                        'page' => $page,
                        'group' => $group,
                        'type' => $type,
                        'label' => 'website::admin.fields.'.$key,
                        'default' => $type === 'image' ? (string) $default : $key,
                        'default_lang' => $type === 'image' ? null : ($isAdmission ? 'admission::messages.'.substr($key, 10) : 'website::messages.'.$key),
                    ];
                }
            }
        }

        return self::$entries = $entries;
    }

    /**
     * page => group => key => type (or [type, committed default path] for an image).
     *
     * @return array<string, array<string, array<string, string|array{0: string, 1: string}>>>
     */
    private static function definitions(): array
    {
        return [
            'home' => [
                'hero' => [
                    'hero.arabic' => 'text', 'hero.tag1' => 'text', 'hero.tag2' => 'text', 'hero.body' => 'textarea',
                    'hero.apply' => 'text', 'hero.call' => 'text', 'hero.abacus_tag' => 'text', 'hero.abacus_sub' => 'text',
                    'hero.badge_est' => 'text', 'hero.badge_hifz' => 'text', 'hero.badge_cadet' => 'text', 'hero.photo_alt' => 'text',
                    'hero_image' => ['image', 'images/site/hero.jpg'],
                ],
                'program' => ['program.kicker' => 'text', 'program.title' => 'text'],
                'phonetics' => ['phonetics.title' => 'text', 'phonetics.sub' => 'textarea'],
                'divisions' => ['divisions.kicker' => 'text', 'divisions.title' => 'text', 'divisions.years' => 'text'],
                'why' => ['why.kicker' => 'text', 'why.title' => 'text'],
                'principal' => [
                    'principal.kicker' => 'text', 'principal.title' => 'text', 'principal.default_message' => 'textarea',
                    'principal.default_name' => 'text', 'principal.role' => 'text', 'principal.photo_alt' => 'text',
                    'principal_photo' => ['image', 'images/site/principal.jpg'],
                ],
                'stats' => ['stats.students' => 'text', 'stats.teachers' => 'text', 'stats.classes' => 'text', 'stats.years' => 'text'],
            ],
            'about' => [
                'about' => [
                    'about.title' => 'text', 'about.kicker' => 'text', 'about.body' => 'textarea', 'about.campus_alt' => 'text',
                    'campus_image' => ['image', 'images/site/campus.jpg'],
                ],
            ],
            'academics' => [
                'academics' => ['academics.title' => 'text', 'academics.schedule_title' => 'text', 'academics.classes_title' => 'text'],
            ],
            'admission' => [
                'admission' => [
                    'admission.page_title' => 'text', 'admission.intro' => 'textarea', 'admission.docs_title' => 'text',
                    'admission.fees_title' => 'text', 'admission.fee_item' => 'text', 'admission.inquiry_title' => 'text',
                    'admission.sent_title' => 'text', 'admission.sent_body' => 'textarea',
                ],
            ],
            'contact' => [
                'contact' => [
                    'contact.title' => 'text', 'contact.address' => 'text', 'contact.phone' => 'text',
                    'contact.hours' => 'text', 'contact.map' => 'text',
                ],
            ],
        ];
    }
}
