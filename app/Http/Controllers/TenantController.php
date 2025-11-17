<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    /**
     * Display the landing page.
     */
    public function landing(): InertiaResponse
    {
        return Inertia::render('Landing', [
            'domain' => config('app.domain', 'domain.test')
        ]);
    }

    /**
     * Display the registration form.
     */
    public function register(): InertiaResponse
    {
        return Inertia::render('Register', [
            'domain' => config('app.domain', 'domain.test')
        ]);
    }

    /**
     * Display a listing of tenants.
     */
    public function index(Request $request): InertiaResponse
    {
        $tenants = Tenant::with('domains')
            ->when($request->search, function ($query, $search) {
                $query->search($search);
            })
            ->when($request->type, function ($query, $type) {
                $query->type($type);
            })
            ->when($request->country, function ($query, $country) {
                $query->country($country);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Central/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'type', 'country']),
            'stats' => [
                'total' => Tenant::count(),
                'professional' => Tenant::professional()->count(),
                'private' => Tenant::private()->count(),
                'recent' => Tenant::where('created_at', '>=', now()->subDays(30))->count(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new tenant.
     */
    public function create(Request $request): InertiaResponse
    {
        // Récupérer le plan depuis l'URL si présent
        $planSlug = $request->query('plan', 'basic');
        $cycle = $request->query('cycle', 'monthly');
        
        $selectedPlan = null;
        if ($planSlug) {
            $plan = \App\Models\SubscriptionPlan::where('slug', $planSlug)
                ->where('is_active', true)
                ->first();
            
            if ($plan) {
                $selectedPlan = [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'monthly_price' => $plan->monthly_price,
                    'yearly_price' => $plan->yearly_price,
                    'formatted_monthly_price' => $plan->formatted_monthly_price,
                    'formatted_yearly_price' => $plan->formatted_yearly_price,
                ];
            }
        }
        
        return Inertia::render('Central/Tenants/Create', [
            'countries' => $this->getCountriesList(),
            'industries' => $this->getIndustriesList(),
            'domain' => config('app.domain', 'domain.test'),
            'selectedPlan' => $selectedPlan,
            'selectedCycle' => $cycle,
        ]);
    }

    /**
     * Store a newly created tenant from the public registration form.
     */
    public function start(Request $request): InertiaResponse|RedirectResponse|Response
    {
        Log::info('Start method called', $request->all());

        $fullDomain = $request->slug . '.' . config('app.domain', 'domain.test');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|alpha_dash', 
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email:rfc|max:255|unique:tenants,contact_email',
            'type' => 'nullable|in:pro,private',
            'admin_password' => ['required', 'confirmed', Rules\Password::defaults()],
            'country' => 'required|string|in:' . implode(',', array_keys($this->getCountriesList())),
            'address' => 'required|string|max:500',
            'postalcode' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'industry' => 'required|string|in:' . implode(',', array_keys($this->getIndustriesList())),
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        ], [
            'contact_email.unique' => 'Cette adresse email est déjà utilisée.',
            'admin_password.min' => 'Password must contain at least 8 characters.',
            'admin_password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'country.required' => 'Le pays est obligatoire.',
            'country.in' => 'Le pays sélectionné n\'est pas valide.',
            'address.required' => 'L\'adresse est obligatoire.',
            'address.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
            'postalcode.required' => 'Le code postal est obligatoire.',
            'postalcode.max' => 'Le code postal ne peut pas dépasser 20 caractères.',
            'city.required' => 'La ville est obligatoire.',
            'city.max' => 'La ville ne peut pas dépasser 255 caractères.',
            'industry.required' => 'Le secteur d\'activité est obligatoire.',
            'industry.in' => 'Le secteur d\'activité sélectionné n\'est pas valide.',
        ]);
        Log::info('✅ Validation passed', ['validated_data' => $validated]);

        if (Domain::where('domain', $fullDomain)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['slug' => 'Cet identifiant est déjà utilisé. Veuillez en choisir un autre.']);
        }

        $tenant = null;
        $tenantDbName = null;
        $tempTenantId = null; 

        try {
            $tempTenantId = \Illuminate\Support\Str::uuid();
            $cleanSlug = strtolower(Str::slug($validated['slug'], '_')); 
            $tenantDbName = 'tenant_' . $cleanSlug;
            
            Log::info('Attempting to create database', ['database_name' => $tenantDbName]);

            try {
                DB::statement("CREATE DATABASE [{$tenantDbName}]");
                Log::info('Tenant database created successfully', ['database' => $tenantDbName]);
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    Log::error('Failed to create database: ' . $e->getMessage(), ['database' => $tenantDbName, 'stack' => $e->getTraceAsString()]);
                    throw new \Exception("Impossible de créer la base de données: " . $e->getMessage());
                }
                Log::info('Database already exists, skipping creation.', ['database' => $tenantDbName]);
            }

            $logoPath = null;
            if ($request->hasFile('logo')) {
                try {
                    // Stocker le logo dans storage/app/public/logos
                    $logoPath = $request->file('logo')->store('logos', 'public');
                    
                    Log::info('Logo uploaded successfully', ['path' => $logoPath]);
                } catch (\Exception $e) {
                    Log::error('Failed to upload logo: ' . $e->getMessage());
                    // Ne pas bloquer l'inscription si le logo échoue
                }
            }
            
            DB::beginTransaction();

            $tenant = Tenant::create([
                'id' => $tempTenantId, 
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'],
                'type' => $validated['type'] ?? 'pro',
                'country' => $validated['country'],
                'address' => $validated['address'],
                'postalcode' => $validated['postalcode'],
                'city' => $validated['city'],
                'industry' => $validated['industry'],
                'logo' => $logoPath,
                'data' => [
                    'created_by' => 'self_registration',
                    'setup_completed' => false,
                    'trial_ends_at' => now()->addDays(30)->toISOString(),
                ]
            ]);

            Log::info('Tenant record created successfully in central DB', ['tenant_id' => $tenant->id]);

            $domain = Domain::create([
                'domain' => $fullDomain,
                'tenant_id' => $tenant->id,
            ]);

            Log::info('Domain record created successfully in central DB', ['domain' => $fullDomain, 'domain_id' => $domain->id]);

            $connectionName = 'tenant_' . str_replace('-', '_', $tenant->id);

            Config::set("database.connections.{$connectionName}", [
                'driver' => 'sqlsrv',
                'host' => config('database.connections.sqlsrv.host'),
                'port' => config('database.connections.sqlsrv.port'),
                'database' => $tenantDbName, 
                'username' => config('database.connections.sqlsrv.username'),
                'password' => config('database.connections.sqlsrv.password'),
                'charset' => config('database.connections.sqlsrv.charset'),
                'prefix' => '',
                'prefix_indexes' => true,
            ]);

            try {
                DB::connection($connectionName)->getPdo();
                Log::info('Database connection successful for tenant', ['connection' => $connectionName, 'database' => $tenantDbName]);
            } catch (\Exception $e) {
                throw new \Exception("Impossible de se connecter à la base de données du tenant '{$tenantDbName}': " . $e->getMessage());
            }

            $migrationPath = 'database/migrations/tenant';
            if (!is_dir(base_path($migrationPath))) {
                if (!mkdir(base_path($migrationPath), 0755, true)) {
                    throw new \Exception("Impossible de créer le dossier de migrations: " . base_path($migrationPath));
                }

                $baseMigrations = [
                    glob(base_path('database/migrations/*_create_users_table.php')),
                    glob(base_path('database/migrations/*_create_password_reset_tokens_table.php')),
                    glob(base_path('database/migrations/*_create_sessions_table.php')),
                ];

                foreach ($baseMigrations as $migrationGroup) {
                    foreach ($migrationGroup as $migration) {
                        if (file_exists($migration)) {
                            $destination = base_path($migrationPath . '/' . basename($migration));
                            if (!copy($migration, $destination)) {
                                Log::warning("Impossible de copier la migration: " . basename($migration) . " vers " . $destination);
                            } else {
                                Log::info("Migration copied: " . basename($migration));
                            }
                        }
                    }
                }
            } else {
                Log::info('Tenant migration directory already exists, skipping creation and copy.', ['path' => $migrationPath]);
            }

            try {
                Artisan::call('migrate', [
                    '--database' => $connectionName,
                    '--path' => $migrationPath,
                    '--force' => true,
                ]);

                $migrationOutput = Artisan::output();
                Log::info('Tenant migrations completed', ['output' => $migrationOutput]);
            } catch (\Exception $e) {
                Log::error('Migration error for tenant ' . $tenant->id . ': ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
                throw new \Exception("Erreur lors de l'exécution des migrations pour le tenant: " . $e->getMessage());
            }

            try {
                $adminExists = DB::connection($connectionName)
                    ->table('users')
                    ->where('email', $validated['contact_email'])
                    ->exists();

                if (!$adminExists) {
                    DB::connection($connectionName)->table('users')->insert([
                        'name' => $validated['contact_name'],
                        'email' => $validated['contact_email'],
                        'password' => Hash::make($validated['admin_password']),
                        'role_id' => 1,
                        'email_verified_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Log::info('Admin user created successfully for tenant ' . $tenant->id);
                } else {
                    Log::info('Admin user already exists for tenant ' . $tenant->id);
                }
            } catch (\Exception $e) {
                Log::error('User creation error for tenant ' . $tenant->id . ': ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
                throw new \Exception("Erreur lors de la création de l'utilisateur admin pour le tenant: " . $e->getMessage());
            }

            // Créer l'abonnement d'essai gratuit de 3 mois
            try {
                // Récupérer le plan sélectionné depuis le formulaire, sinon Basic par défaut
                $planSlug = $request->input('selected_plan_slug', 'basic');
                $billingCycle = $request->input('billing_cycle', 'monthly');
                
                $selectedPlan = \App\Models\SubscriptionPlan::where('slug', $planSlug)
                    ->where('is_active', true)
                    ->first();
                
                if (!$selectedPlan) {
                    // Fallback sur le plan Basic si le plan sélectionné n'existe pas
                    $selectedPlan = \App\Models\SubscriptionPlan::where('slug', 'basic')->first();
                    $billingCycle = 'monthly';
                }
                
                if ($selectedPlan) {
                    $subscription = \App\Models\TenantSubscription::create([
                        'tenant_id' => $tenant->id,
                        'subscription_plan_id' => $selectedPlan->id,
                        'billing_cycle' => $billingCycle,
                        'starts_at' => now()->format('Y-m-d H:i:s'),
                        'trial_ends_at' => now()->addMonths(3)->format('Y-m-d H:i:s'),
                        'ends_at' => null,
                        'status' => 'trial',
                    ]);
                    
                    Log::info('Trial subscription created for tenant', [
                        'tenant_id' => $tenant->id, 
                        'plan' => $selectedPlan->slug,
                        'cycle' => $billingCycle
                    ]);

                    //envoie email de confirmation
                    try {
                        // Recharger la relation subscriptionPlan
                        $subscription->load('subscriptionPlan');
                        
                        $protocol = request()->secure() ? 'https://' : 'http://';
                        $tenantUrl = $protocol . $fullDomain . ':8000/login';
                        
                        Log::info('Attempting to send welcome email', [
                            'to' => $tenant->contact_email,
                            'url' => $tenantUrl
                        ]);
                        
                        \Illuminate\Support\Facades\Mail::to($tenant->contact_email)
                            ->send(new \App\Mail\TenantCreatedMail(
                                tenant: $tenant,
                                subscription: $subscription,
                                tenantUrl: $tenantUrl,
                                password: $validated['admin_password']
                            ));
                        
                        Log::info('Welcome email sent successfully');
                        
                    } catch (\Throwable $emailError) {
                        // IMPORTANT : Ne pas bloquer l'inscription !
                        Log::error('Failed to send welcome email (non-blocking)', [
                            'error' => $emailError->getMessage(),
                            'trace' => $emailError->getTraceAsString(),
                            'tenant_id' => $tenant->id
                        ]);
                        // On continue quand même
                    }

                } else {
                    Log::warning('No subscription plan found, skipping subscription creation');
                }
            } catch (\Exception $e) {
                // Ne pas bloquer l'inscription si la création de l'abonnement échoue
                Log::error('Failed to create trial subscription: ' . $e->getMessage(), [
                    'tenant_id' => $tenant->id ?? 'unknown',
                    'trace' => $e->getTraceAsString()
                ]);
            }

            $tenant->data = array_merge($tenant->data ?? [], ['setup_completed' => true]);
            $tenant->save();
            Log::info('Tenant setup_completed flag updated and saved', ['tenant_id' => $tenant->id]);

            DB::commit();
            Log::info('Transaction committed successfully for tenant ' . $tenant->id);

            // Construire l'URL du tenant avec le protocole approprié
            $protocol = request()->secure() ? 'https://' : 'http://';
            $tenantUrl = $protocol . $fullDomain . ':8000/login';

            return Inertia::render('RegistrationSuccess', [
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'contact_name' => $tenant->contact_name,
                    'contact_email' => $tenant->contact_email,
                    'subdomain' => $fullDomain,
                    'logo' => $tenant->logo,
                ],
                'subscription' => [
                    'plan_name' => $subscription->subscriptionPlan->name ?? 'Basic',
                    'plan_slug' => $subscription->subscriptionPlan->slug ?? 'basic',
                    'billing_cycle' => $subscription->billing_cycle,
                    'trial_ends_at' => $subscription->trial_ends_at?->format('d/m/Y'),
                    'status' => $subscription->status,
                    'monthly_price' => $subscription->subscriptionPlan->monthly_price ?? 0,
                    'yearly_price' => $subscription->subscriptionPlan->yearly_price ?? 0,
                ],
                'tenantUrl' => $tenantUrl,
            ]);

            Log::info('Redirecting to confirmation page');

            return redirect()->route('registration.success');

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollback();
                Log::warning('Transaction rolled back due to error.');
            }

            if ($tenantDbName) {
                try {
                    $dbExists = DB::select("SELECT name FROM master.sys.databases WHERE name = ?", [$tenantDbName]);
                    if (!empty($dbExists)) {
                        DB::statement("DROP DATABASE [{$tenantDbName}]");
                        Log::info('Cleaned up database after error', ['database' => $tenantDbName]);
                    } else {
                        Log::info('Database did not exist for cleanup, skipping drop.', ['database' => $tenantDbName]);
                    }
                } catch (\Exception $cleanupError) {
                    Log::error('Failed to cleanup database: ' . $cleanupError->getMessage(), ['database' => $tenantDbName]);
                }
            }

            if ($tenant && $tenant->exists) {
                try {
                    $tenant->delete();
                    Log::info('Cleaned up tenant record after error', ['tenant_id' => $tenant->id]);
                } catch (\Exception $cleanupError) {
                    Log::error('Failed to cleanup tenant record: ' . $cleanupError->getMessage(), ['tenant_id' => $tenant->id]);
                }
            }

            Log::error('Erreur lors de la création du tenant: ' . $e->getMessage(), [
                'stack' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Une erreur est survenue lors de la création de votre espace: ' . $e->getMessage()]);
        }
    }

    /**
     * Store a newly created tenant (admin interface).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:domains,domain|alpha_dash',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'type' => 'required|in:pro,private',
            'industry' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'postalcode' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:2',
            
            // Pour créer l'utilisateur admin
            'admin_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            // 1. Créer le tenant
            $tenant = Tenant::create([
                'id' => Str::uuid(),
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'],
                'type' => $validated['type'],
                'industry' => $validated['industry'] ?? null,
                'address' => $validated['address'] ?? null,
                'postalcode' => $validated['postalcode'] ?? null,
                'city' => $validated['city'] ?? null,
                'country' => $validated['country'] ?? 'FR',
                'data' => [
                    'created_by' => 'admin',
                    'setup_completed' => false,
                ]
            ]);

            // 2. Créer le domaine
            $domain = $validated['slug'] . '.' . config('app.domain', 'domain.test');
            $tenant->domains()->create([
                'domain' => $domain,
            ]);

            // 3. Créer l'utilisateur admin dans le contexte tenant
            tenancy()->initialize($tenant);
            
            User::create([
                'name' => $validated['contact_name'],
                'email' => $validated['contact_email'],
                'password' => Hash::make($validated['admin_password']),
                'is_admin' => true,
                'email_verified_at' => now(), 
            ]);

            tenancy()->end();

            return redirect()
                ->route('central.tenants.show', $tenant)
                ->with('success', "Tenant '{$tenant->name}' créé avec succès !");

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Erreur lors de la création : ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant): InertiaResponse
    {
        $tenant->load('domains');

        // Récupérer des stats du tenant
        $stats = [];
        try {
            tenancy()->initialize($tenant);
            
            $stats = [
                'users_count' => User::count(),
                'admins_count' => User::where('role_id', 1)->count(),
                'recent_users' => User::where('created_at', '>=', now()->subDays(30))->count(),
                'last_activity' => User::latest('updated_at')->first()?->updated_at,
            ];

            tenancy()->end();
        } catch (\Exception $e) {
            $stats = ['error' => 'Impossible de récupérer les statistiques'];
        }

        return Inertia::render('Central/Tenants/Show', [
            'tenant' => $tenant,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified tenant.
     */
    public function edit(Tenant $tenant): InertiaResponse
    {
        $tenant->load('domains');

        return Inertia::render('Central/Tenants/Edit', [
            'tenant' => $tenant,
            'countries' => $this->getCountriesList(),
            'industries' => $this->getIndustriesList(),
        ]);
    }

    /**
     * Update the specified tenant.
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'type' => 'required|in:pro,private',
            'industry' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'postalcode' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:2',
        ]);

        $tenant->update($validated);

        return redirect()
            ->route('central.tenants.show', $tenant)
            ->with('success', 'Tenant mis à jour avec succès !');
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        try {
            // Stancl/Tenancy se charge automatiquement de supprimer la DB
            $tenant->delete();

            return redirect()
                ->route('central.tenants.index')
                ->with('success', "Tenant '{$tenant->name}' supprimé avec succès !");

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
        }
    }

    /**
     * Impersonate - Se connecter en tant qu'admin du tenant
     */
    public function impersonate(Tenant $tenant): RedirectResponse
    {
        try {
            tenancy()->initialize($tenant);
            
            $adminUser = User::where('role_id', 1)->first();
            
            if (!$adminUser) {
                tenancy()->end();
                return back()->withErrors(['error' => 'Aucun administrateur trouvé pour ce tenant']);
            }

            tenancy()->end();

            // Générer une URL sécurisée pour l'impersonation
            $domain = $tenant->domains()->first();
            if ($domain) {
                $impersonationUrl = "http://{$domain->domain}/admin/impersonate/" . $adminUser->id;
                return redirect()->away($impersonationUrl);
            }

            return back()->withErrors(['error' => 'Aucun domaine configuré pour ce tenant']);

        } catch (\Exception $e) {
            tenancy()->end();
            return back()->withErrors(['error' => 'Erreur d\'impersonation : ' . $e->getMessage()]);
        }
    }

    /**
     * Reinitialize tenant database
     */
    public function reinitialize(Tenant $tenant): RedirectResponse
    {
        try {
            // Relancer les migrations pour ce tenant
            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->id],
                '--force' => true,
            ]);

            return back()->with('success', 'Base de données réinitialisée avec succès !');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur de réinitialisation : ' . $e->getMessage()]);
        }
    }

    /**
     * Get users of a specific tenant
     */
    public function users(Tenant $tenant): InertiaResponse
    {
        $users = [];
        try {
            tenancy()->initialize($tenant);
            
            $users = User::orderBy('created_at', 'desc')
                ->paginate(15);

            tenancy()->end();

        } catch (\Exception $e) {
            $users = collect();
        }

        return Inertia::render('Central/Tenants/Users', [
            'tenant' => $tenant,
            'users' => $users,
        ]);
    }

    /**
     * Export tenant data
     */
    public function export(Tenant $tenant)
    {
        try {
            tenancy()->initialize($tenant);
            
            // Exporter les données principales
            $data = [
                'tenant' => $tenant->toArray(),
                'users' => User::all()->toArray(),
                'exported_at' => now()->toISOString(),
            ];

            tenancy()->end();

            $filename = "tenant_{$tenant->slug}_export_" . now()->format('Y-m-d_H-i-s') . '.json';

            return response()->json($data)
                ->header('Content-Disposition', "attachment; filename={$filename}");

        } catch (\Exception $e) {
            tenancy()->end();
            return back()->withErrors(['error' => 'Erreur d\'export : ' . $e->getMessage()]);
        }
    }

    /**
     * Get countries list
     */
    private function getCountriesList(): array
    {
        return [
            'FR' => 'France',
            'BE' => 'Belgique',
            'CH' => 'Suisse',
            'CA' => 'Canada',
            'US' => 'États-Unis',
            'DE' => 'Allemagne',
            'ES' => 'Espagne',
            'IT' => 'Italie',
            'GB' => 'Royaume-Uni',
        ];
    }

    /**
     * Get industries list
     */
    private function getIndustriesList(): array
    {
        return [
            'technology' => 'Technologie',
            'healthcare' => 'Santé',
            'finance' => 'Finance',
            'education' => 'Éducation',
            'retail' => 'Commerce',
            'manufacturing' => 'Industrie',
            'consulting' => 'Conseil',
            'real_estate' => 'Immobilier',
            'hospitality' => 'Hôtellerie',
            'entertainment' => 'divertissement',
            'other' => 'Autre',
        ];
    }
}