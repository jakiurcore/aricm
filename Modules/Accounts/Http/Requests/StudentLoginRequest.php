<?php

namespace Modules\Accounts\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentLoginRequest extends FormRequest
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
            'student_id' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Only active students may sign in; the "status" column is part of the student provider's contract.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'student_id' => Str::upper(trim($this->string('student_id')->toString())),
            'password' => $this->input('password'),
            'status' => 'active',
        ];

        if (! Auth::guard('student')->attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), config('modules.accounts.login.decay_seconds'));

            throw ValidationException::withMessages(['student_id' => __('accounts::messages.student.failed')]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), config('modules.accounts.login.max_attempts'))) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'student_id' => __('accounts::messages.login.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey())]),
        ]);
    }

    protected function throttleKey(): string
    {
        return 'student|'.Str::upper($this->string('student_id')->toString()).'|'.$this->ip();
    }
}
