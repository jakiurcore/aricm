<?php

namespace Modules\Website\Http\Requests;

class ImageRequest extends ManageRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192']];
    }
}
