<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    /**
     * Afficher tous les plans disponibles
     */
    public function index()
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('monthly_price')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'monthly_price' => $plan->monthly_price,
                    'yearly_price' => $plan->yearly_price,
                    'formatted_monthly_price' => $plan->formatted_monthly_price,
                    'formatted_yearly_price' => $plan->formatted_yearly_price,
                    'yearly_savings_percentage' => $plan->yearly_savings_percentage,
                    'yearly_savings_amount' => $plan->yearly_savings_amount,
                    'has_trial' => $plan->has_trial,
                    'trial_days' => $plan->trial_days,
                    'max_employees' => $plan->max_employees,
                    'is_free' => $plan->isFree(),
                ];
            });

        // Vérifier si un tenant existe 
        $currentSubscription = null;
        $tenant = tenant(); 
        
        if ($tenant) {
            $subscription = TenantSubscription::with('subscriptionPlan')
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->first();
            
            if ($subscription) {
                $currentSubscription = $this->formatSubscription($subscription);
            }
        }

        return Inertia::render('Pricing', [
            'plans' => $plans,
            'currentSubscription' => $currentSubscription,
        ]);
    }

    /**
     * Afficher l'abonnement actuel du tenant
     */
    public function show()
    {
        $tenant = tenant();
        
        $subscription = TenantSubscription::with('subscriptionPlan')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->first();

        if (!$subscription) {
            return redirect()->route('subscriptions.index');
        }

        return Inertia::render('Subscriptions/Show', [
            'subscription' => $this->formatSubscription($subscription),
            'availablePlans' => SubscriptionPlan::active()
                ->where('id', '!=', $subscription->subscription_plan_id)
                ->get()
                ->map(fn($plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'monthly_price' => $plan->monthly_price,
                    'yearly_price' => $plan->yearly_price,
                    'formatted_monthly_price' => $plan->formatted_monthly_price,
                    'formatted_yearly_price' => $plan->formatted_yearly_price,
                ]),
        ]);
    }

    /**
     * Créer un nouvel abonnement (essai gratuit de 3 mois)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        // Récupérer le tenant - avec vérification
        $tenant = tenant();
        
        if (!$tenant) {
            return back()->with('error', 'Impossible d\'identifier votre organisation. Veuillez vous reconnecter.');
        }

        $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);

        // Vérifier si le tenant a déjà un abonnement actif
        $existingSubscription = TenantSubscription::where('tenant_id', $tenant->id)
            ->whereIn('status', [
                TenantSubscription::STATUS_TRIAL,
                TenantSubscription::STATUS_ACTIVE
            ])
            ->first();

        if ($existingSubscription) {
            return back()->with('error', 'Vous avez déjà un abonnement actif.');
        }

        DB::beginTransaction();
        try {
            // Créer l'abonnement avec essai de 3 mois
            $subscription = TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => $validated['billing_cycle'],
                'starts_at' => now(),
                'trial_ends_at' => now()->addMonths(3), // 3 mois d'essai gratuit
                'ends_at' => null,
                'status' => TenantSubscription::STATUS_TRIAL,
            ]);

            DB::commit();

            return back()->with('success', 'Votre période d\'essai gratuite de 3 mois a commencé !');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subscription creation error: ' . $e->getMessage(), [
                'tenant_id' => $tenant->id ?? 'unknown',
                'plan_id' => $validated['subscription_plan_id'],
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Erreur lors de la création de l\'abonnement: ' . $e->getMessage());
        }
    }


    /**
     * Activer l'abonnement après la période d'essai
     */
    public function activate(TenantSubscription $subscription)
    {
        $tenant = tenant();

        if ($subscription->tenant_id !== $tenant->id) {
            abort(403);
        }

        if (!$subscription->isOnTrial()) {
            return back()->with('error', 'Cet abonnement n\'est pas en période d\'essai.');
        }

        DB::beginTransaction();
        try {
            $subscription->update([
                'status' => TenantSubscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'ends_at' => $subscription->calculateNextBillingDate(),
                'trial_ends_at' => null,
            ]);

            DB::commit();

            // Ici vous pouvez déclencher le paiement avec Stripe/PayPal
            // $this->processPayment($subscription);

            return back()->with('success', 'Votre abonnement est maintenant actif !');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Erreur lors de l\'activation de l\'abonnement.');
        }
    }

    /**
     * Changer de plan
     */
    public function changePlan(Request $request, TenantSubscription $subscription)
    {
        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'sometimes|in:monthly,yearly',
        ]);

        $tenant = tenant();

        if ($subscription->tenant_id !== $tenant->id) {
            abort(403);
        }

        if (!$subscription->isValid()) {
            return back()->with('error', 'Cet abonnement n\'est pas valide.');
        }

        $newPlan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);
        $billingCycle = $validated['billing_cycle'] ?? $subscription->billing_cycle;

        DB::beginTransaction();
        try {
            // Calculer le prorata si nécessaire
            $prorataInfo = $this->calculateProrata($subscription, $newPlan, $billingCycle);

            $subscription->update([
                'subscription_plan_id' => $newPlan->id,
                'billing_cycle' => $billingCycle,
            ]);

            DB::commit();

            // Si upgrade, traiter le paiement du prorata
            if ($prorataInfo['type'] === 'charge') {
                // $this->processProrata($prorataInfo);
            }

            return back()->with('success', 'Votre plan a été mis à jour avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Erreur lors du changement de plan.');
        }
    }

    /**
     * Changer le cycle de facturation
     */
    public function changeBillingCycle(Request $request, TenantSubscription $subscription)
    {
        $validated = $request->validate([
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $tenant = tenant();

        if ($subscription->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($subscription->billing_cycle === $validated['billing_cycle']) {
            return back()->with('info', 'Vous êtes déjà sur ce cycle de facturation.');
        }

        DB::beginTransaction();
        try {
            $subscription->update([
                'billing_cycle' => $validated['billing_cycle'],
                'ends_at' => $subscription->calculateNextBillingDate(),
            ]);

            DB::commit();

            $cycle = $validated['billing_cycle'] === 'yearly' ? 'annuel' : 'mensuel';
            
            return back()->with('success', "Votre cycle de facturation a été changé en $cycle !");

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Erreur lors du changement de cycle.');
        }
    }

    /**
     * Renouveler l'abonnement
     */
    public function renew(TenantSubscription $subscription)
    {
        $tenant = tenant();

        if ($subscription->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($subscription->isActive()) {
            return back()->with('info', 'Votre abonnement est déjà actif.');
        }

        DB::beginTransaction();
        try {
            $subscription->renew();

            DB::commit();

            // Ici vous pouvez déclencher le paiement
            // $this->processPayment($subscription);

            return back()->with('success', 'Votre abonnement a été renouvelé !');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Erreur lors du renouvellement.');
        }
    }

    /**
     * Annuler l'abonnement
     */
    public function cancel(TenantSubscription $subscription)
    {
        $tenant = tenant();

        if ($subscription->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($subscription->isCancelled()) {
            return back()->with('info', 'Cet abonnement est déjà annulé.');
        }

        DB::beginTransaction();
        try {
            $subscription->cancel();

            DB::commit();

            return back()->with('success', 'Votre abonnement a été annulé. Il restera actif jusqu\'à la fin de la période payée.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Erreur lors de l\'annulation.');
        }
    }

    /**
     * Reprendre un abonnement annulé
     */
    public function resume(TenantSubscription $subscription)
    {
        $tenant = tenant();

        if ($subscription->tenant_id !== $tenant->id) {
            abort(403);
        }

        if (!$subscription->isCancelled()) {
            return back()->with('error', 'Cet abonnement n\'est pas annulé.');
        }

        DB::beginTransaction();
        try {
            $subscription->update([
                'status' => TenantSubscription::STATUS_ACTIVE,
            ]);

            DB::commit();

            return back()->with('success', 'Votre abonnement a été réactivé !');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Erreur lors de la réactivation.');
        }
    }

    /**
     * Formater l'abonnement pour Inertia
     */
    private function formatSubscription(TenantSubscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'billing_cycle' => $subscription->billing_cycle,
            'status' => $subscription->status,
            'formatted_status' => $subscription->formatted_status,
            'status_color' => $subscription->status_color,
            'starts_at' => $subscription->starts_at?->format('Y-m-d H:i:s'),
            'ends_at' => $subscription->ends_at?->format('Y-m-d H:i:s'),
            'trial_ends_at' => $subscription->trial_ends_at?->format('Y-m-d H:i:s'),
            'remaining_trial_days' => $subscription->remaining_trial_days,
            'remaining_days' => $subscription->remaining_days,
            'price' => $subscription->price,
            'is_on_trial' => $subscription->isOnTrial(),
            'is_active' => $subscription->isActive(),
            'is_valid' => $subscription->isValid(),
            'is_expired' => $subscription->isExpired(),
            'is_cancelled' => $subscription->isCancelled(),
            'is_suspended' => $subscription->isSuspended(),
            'plan' => [
                'id' => $subscription->subscriptionPlan->id,
                'name' => $subscription->subscriptionPlan->name,
                'slug' => $subscription->subscriptionPlan->slug,
                'description' => $subscription->subscriptionPlan->description,
                'monthly_price' => $subscription->subscriptionPlan->monthly_price,
                'yearly_price' => $subscription->subscriptionPlan->yearly_price,
                'formatted_monthly_price' => $subscription->subscriptionPlan->formatted_monthly_price,
                'formatted_yearly_price' => $subscription->subscriptionPlan->formatted_yearly_price,
                'max_employees' => $subscription->subscriptionPlan->max_employees,
                'has_employee_limit' => $subscription->subscriptionPlan->hasEmployeeLimit(),
            ],
            'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $subscription->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Calculer le prorata lors d'un changement de plan
     */
    private function calculateProrata(TenantSubscription $subscription, SubscriptionPlan $newPlan, string $billingCycle): array
    {
        $oldPrice = $subscription->price;
        $newPrice = $billingCycle === 'yearly' ? $newPlan->yearly_price : $newPlan->monthly_price;
        
        $remainingDays = $subscription->remaining_days ?? 0;
        $totalDays = $subscription->billing_cycle === 'yearly' ? 365 : 30;

        if ($remainingDays <= 0) {
            return [
                'type' => 'none',
                'amount' => 0,
            ];
        }

        $priceDifference = $newPrice - $oldPrice;
        $prorataAmount = abs($priceDifference * ($remainingDays / $totalDays));

        return [
            'type' => $priceDifference > 0 ? 'charge' : 'credit',
            'amount' => round($prorataAmount, 2),
            'remaining_days' => $remainingDays,
            'total_days' => $totalDays,
        ];
    }

    /**
     * Vérifier et expirer les abonnements (Cron Job)
     */
    public function checkExpiredSubscriptions()
    {
        // Expirer les essais gratuits terminés
        $expiredTrials = TenantSubscription::where('status', TenantSubscription::STATUS_TRIAL)
            ->where('trial_ends_at', '<=', now())
            ->get();

        foreach ($expiredTrials as $subscription) {
            $subscription->update(['status' => TenantSubscription::STATUS_EXPIRED]);
            // Envoyer notification
            // Notification::send($subscription->tenant->users, new TrialExpiredNotification());
        }

        // Expirer les abonnements actifs arrivés à échéance
        $expiredActive = TenantSubscription::where('status', TenantSubscription::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($expiredActive as $subscription) {
            $subscription->expire();
            // Envoyer notification
            // Notification::send($subscription->tenant->users, new SubscriptionExpiredNotification());
        }

        return response()->json([
            'message' => 'Abonnements vérifiés',
            'expired_trials' => $expiredTrials->count(),
            'expired_active' => $expiredActive->count(),
        ]);
    }
}