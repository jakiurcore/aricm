<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Settings the admin form may write. Files (logo, signature) go through the upload endpoint instead.
     *
     * @return array<string, array<int, string>>
     */
    public static function fieldRules(): array
    {
        return [
            'institute_name_bn' => ['required', 'string', 'max:255'],
            'institute_name_en' => ['nullable', 'string', 'max:255'],
            'institute_name_ar' => ['nullable', 'string', 'max:255'],
            'tagline_bn' => ['nullable', 'string', 'max:255'],
            'tagline_en' => ['nullable', 'string', 'max:255'],
            'established_year' => ['nullable', 'integer', 'between:1900,2100'],
            'address_bn' => ['nullable', 'string', 'max:500'],
            'address_en' => ['nullable', 'string', 'max:500'],
            'phones' => ['array', 'max:5'],
            'phones.*' => ['string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'principal_name_bn' => ['nullable', 'string', 'max:255'],
            'principal_name_en' => ['nullable', 'string', 'max:255'],
            'principal_message_bn' => ['nullable', 'string', 'max:5000'],
            'principal_message_en' => ['nullable', 'string', 'max:5000'],
            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'admit_card_rules_bn' => ['nullable', 'string', 'max:3000'],
            'admit_card_rules_en' => ['nullable', 'string', 'max:3000'],
            'result_requires_fee_paid' => ['boolean'],
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
