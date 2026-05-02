<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class LoginRequest extends FormRequest
{
    private function loginErrorResponse(string $message, string $emailError = '', string $passwordError = '', int $status = 422): never
    {
        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => [
                'email' => $emailError,
                'password' => $passwordError,
            ],
        ], $status));
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $emailError = $validator->errors()->first('email') ?: '';
        $passwordError = $validator->errors()->first('password') ?: '';
        $message = $emailError ?: ($passwordError ?: 'Validation failed.');

        $this->loginErrorResponse($message, $emailError, $passwordError);
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = Str::lower((string) $this->input('email'));
        $password = (string) $this->input('password');

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            RateLimiter::hit($this->throttleKey());

            $this->loginErrorResponse(
                'This email does not match our records.',
                'This email does not match our records.',
                ''
            );
        }

        if (! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            $this->loginErrorResponse(
                'This password does not match our records.',
                '',
                'This password does not match our records.'
            );
        }

        Auth::login($user, $this->boolean('remember'));
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $message = trans('auth.throttle', [
            'seconds' => $seconds,
            'minutes' => ceil($seconds / 60),
        ]);

        $this->loginErrorResponse($message, $message, $message);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }
}
