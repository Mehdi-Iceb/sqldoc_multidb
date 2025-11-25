<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $tenant = tenant();

        Log::warning('INERTIA SHARE → tenant = ' . ($tenant?->id ?? 'NULL'), [
            'host' => $request->getHost(),
            'url' => $request->path(),
            'user_id' => auth()->id(),
        ]);
        
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role?->name?? null,
                ] : null,
            ],
            'tenant' => function () {
                if (app()->has('tenant')) {
                    $tenant = app('tenant');
                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'logo' => $tenant->logo ? asset('storage/' . $tenant->logo) : null,
                        'subdomain' => $tenant->subdomain ?? null,
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
            
            'domain' => config('app.domain'),
        ]);
    }
}
