<?php

namespace Modules\Results\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'exam_id' => ['required', 'integer'],
            'class_id' => ['required', 'integer'],
            'identifier' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before:today'],
        ];
    }
}
