<?php

namespace Modules\Results\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class GradingScaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('results.grading');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'is_default' => ['boolean'],
            'items' => ['required', 'array', 'min:2', 'max:15'],
            'items.*.min_percent' => ['required', 'numeric', 'between:0,100'],
            'items.*.max_percent' => ['required', 'numeric', 'between:0,100', 'gte:items.*.min_percent'],
            'items.*.grade' => ['required', 'string', 'max:10'],
            'items.*.grade_point' => ['required', 'numeric', 'between:0,9.99'],
        ];
    }

    /**
     * A scale must start at 0% (so every mark gets a grade) and no two bands may begin at the same percentage.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $mins = array_map(fn (array $item): float => (float) ($item['min_percent'] ?? -1), $this->input('items', []));

            if (! in_array(0.0, $mins, true)) {
                $validator->errors()->add('items', __('results::messages.scale_needs_zero'));
            }

            if (count($mins) !== count(array_unique($mins))) {
                $validator->errors()->add('items', __('results::messages.scale_duplicate'));
            }
        }];
    }
}
