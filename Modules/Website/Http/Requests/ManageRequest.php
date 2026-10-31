<?php

namespace Modules\Website\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base for every write to the website content: only users with website.manage get through.
 */
abstract class ManageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('website.manage');
    }
}
