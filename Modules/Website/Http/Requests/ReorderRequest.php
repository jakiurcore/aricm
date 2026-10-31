<?php

namespace Modules\Website\Http\Requests;

class ReorderRequest extends ManageRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['ids' => ['required', 'array', 'max:200'], 'ids.*' => ['integer']];
    }
}
