<?php

namespace Modules\Website\Http\Requests;

use Closure;
use Illuminate\Validation\Rule;

class ItemRequest extends ManageRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'title_bn' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'body_bn' => ['nullable', 'string', 'max:5000'],
            'body_en' => ['nullable', 'string', 'max:5000'],
            'icon' => ['nullable', 'string', 'max:40'],
            'meta' => ['nullable', 'array'],
            'meta.*' => ['nullable', fn (string $attribute, mixed $value, Closure $fail) => is_scalar($value) && mb_strlen((string) $value) <= 255 ? null : $fail('The meta value is invalid.')],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_image' => ['boolean'],
        ];

        if ($this->route('collection') === 'divisions') {
            $current = $this->route('item');
            $rules['meta'] = ['required', 'array'];
            $rules['meta.key'] = ['required', 'string', 'alpha_dash', 'max:40', Rule::unique('website_items', 'meta->key')->where('collection', 'divisions')->ignore($current)];
        }

        return $rules;
    }
}
