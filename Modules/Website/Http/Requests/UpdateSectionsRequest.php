<?php

namespace Modules\Website\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Website\Services\HomepageComposer;

class UpdateSectionsRequest extends ManageRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'sections' => ['required', 'array', 'max:50'],
            'sections.*.key' => ['required', 'string', Rule::in(array_column(app(HomepageComposer::class)->catalog(), 'key'))],
            'sections.*.is_enabled' => ['required', 'boolean'],
            'sections.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
