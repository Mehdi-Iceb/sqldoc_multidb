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
        try{
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

                'appName' => config('app.name', 'Laravel'),
                'appVersion' => config('app.version', null),
                
                'currentProject' => fn() => session('current_project'),
                
                'navigationData' => function () {
                    // ✅ Retourner des données vides si pas de projet sélectionné
                    $currentProject = session('current_project');
                    
                    if (!$currentProject) {
                        return [
                            'tables' => [],
                            'views' => [],
                            'functions' => [],
                            'procedures' => [],
                            'triggers' => [],
                            'metadata' => [
                                'generated_at' => now()->toIso8601String(),
                                'execution_time_ms' => 0,
                                'total_objects' => 0,
                                'message' => 'Aucune base de données sélectionnée'
                            ]
                        ];
                    }
                    
                    // ✅ Charger les données de navigation depuis le cache/session
                    return session('navigation_data', [
                        'tables' => [],
                        'views' => [],
                        'functions' => [],
                        'procedures' => [],
                        'triggers' => [],
                        'metadata' => [
                            'generated_at' => now()->toIso8601String(),
                            'execution_time_ms' => 0,
                            'total_objects' => 0,
                            'message' => 'No data'
                        ]
                    ]);
                },
                
                'permissions' => fn() => [], // Ajoutez vos permissions si nécessaire
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ CRITICAL ERROR in HandleInertiaRequests::share()', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        
        // Retourner un minimum pour ne pas bloquer
        return [
            'auth' => ['user' => null],
            'csrf_token' => csrf_token(),
        ];
    }
    }
}