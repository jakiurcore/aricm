<?php

namespace Modules\Students\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Students\Models\Student;

class ImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('import', Student::class);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'dry_run' => ['boolean'],
        ];
    }
}
