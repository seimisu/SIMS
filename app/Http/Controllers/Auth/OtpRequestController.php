<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\OtpRequest;
use App\Mail\OtpRequestMail;
use App\Models\OtpRequests;
use App\Models\User;
use App\Support\IdempotencyGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OtpRequestController extends Controller
{
    public function create(LoginRequest $request)
    {
        $request->otpAuthenticate();
        $user = User::where('email', $request['email'])->with('profile')->first();
        $genToken = random_int(100000, 999999);

        $otp = OtpRequests::where('user_id', $user->id)->where('status', 'new')->first();

        if (!$otp) {
            OtpRequests::create([
                'generated_token' => $genToken,
                'user_id' => $user->id,
                'attempts' => 1,
                'expires_at' => now()->addMinutes(2),
            ]);


            IdempotencyGuard::forSeconds("otp-email:{$user->id}", 30, fn () => Mail::to($user->email)->send(new OtpRequestMail($genToken, $user->profile->fullname)));

            return back()->with('flash', [
                'message' => 'We sent an OTP to your email.',
                'status' => 'success',
            ]);
        }

        $sent = IdempotencyGuard::forSeconds("otp-email:{$user->id}", 30, function () use ($otp, $genToken, $user) {
            $otp->increment('attempts');

            $otp->update([
                'generated_token' => $genToken,
                'expires_at' => now()->addMinutes(2 * $otp->attempts),
            ]);

            Mail::to($user->email)->send(new OtpRequestMail($genToken, $user->profile->fullname));
        });

        return back()->with('flash', [
            'message' => $sent ? 'We re-sent a new OTP to your email.' : 'An OTP was recently sent. Please check your email before requesting another one.',
            'status' => $sent ? 'success' : 'info',
            'attempts' => $otp->attempts
        ]);
    }

    public function store(OtpRequest $request)
    {
        $request->userAuth();



        $user = User::firstWhere('email', $request['email']);

        OtpRequests::where('user_id', $user->id)->update([
            'status' => 'done'
        ]);


        Auth::login($user);
        session()->regenerate();
        return redirect()->intended('/dashboard');
    }
}
