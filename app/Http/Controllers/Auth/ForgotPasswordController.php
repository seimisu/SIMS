<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ForgotPasswordController extends Controller
{
    public function create($token)
    {
        return Inertia::render('Auth/RPasswordPage', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }


    public function store(ForgotPasswordRequest $request)
    {
        $request->sendPasswordResetLink();

        return back()->with('status', 'Password reset link sent!');
    }

    public function update(ResetPasswordRequest $request)
    {
        $data = $request->validated();

        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );
        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->back()
                ->with('status', 'Password has been reset!');
        }

        return back()->withErrors([
            'email' => __($status),
        ]);
    }
}