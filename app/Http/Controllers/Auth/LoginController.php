<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {

        return Inertia::render('Auth/loginPage');
    }

    public function twoFactorChallenge(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect('/login');
        }

        return Inertia::render('Auth/loginPage', [
            'twoFactorRequired' => true,
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse|Response
    {
        if ($request->authenticate()) {
            return Inertia::render('Auth/loginPage', [
                'twoFactorRequired' => true,
            ]);
        }

        session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
