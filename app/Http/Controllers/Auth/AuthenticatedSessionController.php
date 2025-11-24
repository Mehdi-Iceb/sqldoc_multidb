<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {

         Log::info('🔐 Login attempt started', [
        'email' => $request->email,
        'is_inertia' => $request->header('X-Inertia'),
    ]);
        $request->authenticate();

        Log::info('✅ Authentication successful', [
        'user_id' => Auth::id(),
        'user_email' => Auth::user()->email,
    ]);

        $request->session()->regenerate();

        Log::info('🔄 Session regenerated', [
        'session_id' => session()->getId(),
    ]);

    $redirectUrl = route('projects.index', absolute: false);

    Log::info('↗️ Redirecting to', [
        'url' => $redirectUrl,
        'intended' => $request->session()->get('url.intended'),
    ]);

        return redirect()->intended('/projects');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/projects');
    }
}
