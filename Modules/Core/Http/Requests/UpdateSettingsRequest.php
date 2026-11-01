<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Settings the admin form may write. Files (logo, signature) go through the upload endpoint instead. `nav_items` is a list of
     * {label_bn, label_en, route|url, visible, sort_order, location: header|footer}.
     *
     * @return array<string, array<int, string>>
     */
    public static function fieldRules(): array
    {
        return [
            'institute_name_bn' => ['required', 'string', 'max:255'],
            'institute_name_en' => ['nullable', 'string', 'max:255'],
            'institute_name_ar' => ['nullable', 'string', 'max:255'],
            'institute_program_bn' => ['nullable', 'string', 'max:200'],
            'institute_program_en' => ['nullable', 'string', 'max:200'],
            'tagline_bn' => ['nullable', 'string', 'max:255'],
            'tagline_en' => ['nullable', 'string', 'max:255'],
            'established_year' => ['nullable', 'integer', 'between:1900,2100'],
            'address_bn' => ['nullable', 'string', 'max:500'],
            'address_en' => ['nullable', 'string', 'max:500'],
            'phones' => ['array', 'max:5'],
            'phones.*' => ['string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'institute_short_name_bn' => ['nullable', 'string', 'max:100'],
            'institute_short_name_en' => ['nullable', 'string', 'max:100'],
            'principal_title_bn' => ['nullable', 'string', 'max:255'],
            'principal_title_en' => ['nullable', 'string', 'max:255'],
            'office_hours_bn' => ['nullable', 'string', 'max:255'],
            'office_hours_en' => ['nullable', 'string', 'max:255'],
            'principal_name_bn' => ['nullable', 'string', 'max:255'],
            'principal_name_en' => ['nullable', 'string', 'max:255'],
            'principal_message_bn' => ['nullable', 'string', 'max:5000'],
            'principal_message_en' => ['nullable', 'string', 'max:5000'],
            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'social_whatsapp' => ['nullable', 'string', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'map_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'map_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'nav_items' => ['nullable', 'array', 'max:40'],
            'nav_items.*.label_bn' => ['required', 'string', 'max:100'],
            'nav_items.*.label_en' => ['nullable', 'string', 'max:100'],
            'nav_items.*.route' => ['nullable', 'string', 'max:100', 'required_without:nav_items.*.url'],
            'nav_items.*.url' => ['nullable', 'string', 'max:500', 'required_without:nav_items.*.route'],
            'nav_items.*.visible' => ['boolean'],
            'nav_items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'nav_items.*.location' => ['required', 'in:header,footer'],
            'admit_card_rules_bn' => ['nullable', 'string', 'max:3000'],
            'admit_card_rules_en' => ['nullable', 'string', 'max:3000'],
            'result_requires_fee_paid' => ['boolean'],
            'teacher_marks_entry_enabled' => ['boolean'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return self::fieldRules();
    }
}
