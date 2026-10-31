<?php

namespace Modules\Website\Http\Requests;

use Illuminate\Validation\Validator;
use Modules\Website\Services\SiteContent;

class PriceRowRequest extends ManageRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'label_bn' => ['required', 'string', 'max:255'],
            'label_en' => ['nullable', 'string', 'max:255'],
            'note_bn' => ['nullable', 'string', 'max:255'],
            'note_en' => ['nullable', 'string', 'max:255'],
            'prices' => ['nullable', 'array'],
            'prices.*' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ];
    }

    /**
     * Prices may only be keyed by an existing division.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $allowed = app(SiteContent::class)->priceColumns()->keys()->all();

            foreach (array_keys((array) $this->input('prices')) as $key) {
                if (! in_array($key, $allowed, true)) {
                    $validator->errors()->add('prices', "Unknown division: {$key}");
                }
            }
        }];
    }
}
