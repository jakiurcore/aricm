<?php

namespace Modules\Admission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Admission\Services\AdmissionSettings;

class InquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'student_name' => ['required', 'string', 'max:255'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
            'desired_class' => ['nullable', 'string', Rule::in(app(AdmissionSettings::class)->classes())],
            'message' => ['nullable', 'string', 'max:1000'],
            // A hidden field real visitors never fill in; bots do.
            'website' => ['nullable', 'max:0'],
        ];
    }

    /**
     * Accept Bangla digits in the phone number.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => preg_replace('/[\s\-]/', '', strtr((string) $this->input('phone'), ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']))]);
        }
    }
}
