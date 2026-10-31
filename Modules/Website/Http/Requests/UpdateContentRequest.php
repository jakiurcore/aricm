<?php

namespace Modules\Website\Http\Requests;

use Illuminate\Validation\Validator;
use Modules\Website\Services\ContentSchema;

class UpdateContentRequest extends ManageRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['values' => ['required', 'array']];
    }

    /**
     * Keys contain dots, so each entry is checked by hand instead of with wildcard rules.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ((array) $this->input('values') as $key => $value) {
                $entry = ContentSchema::find((string) $key);

                if ($entry === null || $entry['type'] === 'image') {
                    $validator->errors()->add('values', "Unknown text key: {$key}");

                    continue;
                }

                foreach (['bn', 'en'] as $locale) {
                    $text = is_array($value) ? ($value[$locale] ?? null) : null;

                    if ($text !== null && (! is_string($text) || mb_strlen($text) > 5000)) {
                        $validator->errors()->add('values', "Invalid {$locale} text for {$key}");
                    }
                }
            }
        }];
    }
}
