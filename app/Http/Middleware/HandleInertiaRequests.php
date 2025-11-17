<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role->name,
                ] : null,
            ],
            'tenant' => function () use ($request) {
                // Vérifier si on est dans un contexte tenant
                if (function_exists('tenancy') && tenancy()->initialized) {
                    $tenant = tenancy()->tenant;
                    
                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'logo' => $tenant->logo ? asset('storage/' . $tenant->logo) : null,
                        'subdomain' => $request->getHost(),
                    ];
                }
                
                return null;
            },
            'csrf_token' => csrf_token(),
            'flash' => function () use ($request) {
                return [
                    'success' => $request->session()->get('success'),
                ];
            },
            'showingMobileMenu' => false,
        ]);
    }
}
