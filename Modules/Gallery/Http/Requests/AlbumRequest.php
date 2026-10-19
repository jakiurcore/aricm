<?php

namespace Modules\Gallery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gallery.manage');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title_bn' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description_bn' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'album_date' => ['nullable', 'date'],
            'is_published' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'between:0,9999'],
        ];
    }
}
