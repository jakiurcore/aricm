<?php

namespace Modules\Notices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Notices\Enums\NoticeCategory;

class NoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('notices.manage');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title_bn' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'body_bn' => ['required', 'string', 'max:20000'],
            'body_en' => ['nullable', 'string', 'max:20000'],
            'category' => ['required', Rule::enum(NoticeCategory::class)],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'remove_attachment' => ['boolean'],
            'is_pinned' => ['boolean'],
            'show_in_ticker' => ['boolean'],
            'is_published' => ['boolean'],
            'publish_at' => ['nullable', 'date'],
        ];
    }
}
