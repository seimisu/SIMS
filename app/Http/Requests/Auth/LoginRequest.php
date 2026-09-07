<?php

namespace App\Http\Requests\Auth;

use App\Models\OtpRequests;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginRequest extends FormRequest
{
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
        if ($this->input('otpRequest')) {
            return [
                'email' => ['required', 'string', 'email'],
            ];
        } else {
            return [
                'email' => ['required'],
                'password' => ['required'],
            ];
        }
    }

    public function authenticate(): bool
    {

        $this->ensureIsNotRateLimited();
        $user = User::where('email', $this->input('email'))->first();


        if (!$user) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }


        if (!$user->is_active) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Your account is deactivated. Check your email to activate or Please contact support.',
            ]);
        }

        if ($user->is_delete) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Your account has been deleted. Please contact support.',
            ]);
        }



        if (! Hash::check($this->input('password'), $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        if ($user->two_factor_secret) {
            $this->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $this->boolean('remember'),
            ]);

            return true;
        }

        Auth::login($user, $this->boolean('remember'));

        return false;
    }


    public function otpAuthenticate(): void
    {

        $this->ensureIsNotRateLimited();
        $user = User::where('email', $this->input('email'))->first();


        if (!$user) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $checkExpired = OtpRequests::where('user_id', $user->id)->where('expires_at', '>', now())->where('status', 'new')->first();

        if ($checkExpired) {
            $remaining = now()->diffInSeconds($checkExpired->expires_at);

            $minutes = floor($remaining / 60);
            $seconds = $remaining % 60;



            throw ValidationException::withMessages([
                'email' => "Please wait {$minutes}m {$seconds}s before requesting a new OTP."
            ]);
        }


        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}
