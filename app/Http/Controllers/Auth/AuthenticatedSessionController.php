<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        
        $request->authenticate();

    //     dd([
    //     'step' => 'After authenticate',
    //     'user' => Auth::user(),
    //     'user_id' => Auth::id(),
    //     'session_id' => session()->getId(),
    // ]);

        $request->session()->regenerate();

    //      dd([
    //     'step' => 'After regenerate',
    //     'user' => Auth::user(),
    //     'user_id' => Auth::id(),
    //     'session_id' => session()->getId(),
    //     'redirect_url' => route('projects.index', absolute: false),
    // ]);

        $response = redirect()->intended(route('projects.index', absolute: false));

        dd([
        'step' => 'Response created',
        'response_class' => get_class($response),
        'response_status' => $response->getStatusCode(),
        'response_target' => $response->getTargetUrl(),
        'is_redirect' => $response->isRedirect(),
    ]);

    return $response;
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
