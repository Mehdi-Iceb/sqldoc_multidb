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
        $request->authenticate();
        $request->session()->regenerate();

        // Récupération du tenant courant
        $tenant = tenant();
        if (!$tenant) {
            Log::warning('Login → tenant is null!', ['host' => $request->getHost()]);
        } else {
            Log::info('Login → tenant detected', ['tenant_id' => $tenant->id, 'tenant_slug' => $tenant->slug]);
        }

        $redirectUrl = route('projects.index');

        // Si la requête vient d’Inertia (XHR SPA)
        if ($request->header('X-Inertia')) {
            Log::info('Login → Inertia request, using Inertia::location', ['redirect_url' => $redirectUrl]);
            return Inertia::location($redirectUrl);
        }

        // Requête classique (non-XHR)
        Log::info('Login → classic redirect', ['redirect_url' => $redirectUrl]);
        return redirect()->intended($redirectUrl);
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
