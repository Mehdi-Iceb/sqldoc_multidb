<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    // Constantes pour les statuts
    const STATUS_TRIAL = 'trial';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_EXPIRED = 'expired';

    // Constantes pour les cycles de facturation
    const BILLING_MONTHLY = 'monthly';
    const BILLING_YEARLY = 'yearly';

    /**
     * Relation avec le tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Relation avec le plan d'abonnement
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Scope pour les abonnements actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope pour les abonnements en période d'essai
     */
    public function scopeTrial($query)
    {
        return $query->where('status', self::STATUS_TRIAL);
    }

    /**
     * Scope pour les abonnements expirés
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    /**
     * Scope pour les abonnements valides (actifs ou en essai)
     */
    public function scopeValid($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIAL]);
    }

    /**
     * Scope pour les abonnements qui arrivent à expiration
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }

    /**
     * Vérifier si l'abonnement est en période d'essai
     */
    public function isOnTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL 
            && $this->trial_ends_at 
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Vérifier si l'abonnement est actif
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Vérifier si l'abonnement est valide (actif ou en essai)
     */
    public function isValid(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIAL])
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Vérifier si l'abonnement est expiré
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED 
            || ($this->ends_at && $this->ends_at->isPast());
    }

    /**
     * Vérifier si l'abonnement est annulé
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Vérifier si l'abonnement est suspendu
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Vérifier si la période d'essai est terminée
     */
    public function hasTrialEnded(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Obtenir le nombre de jours restants de l'essai
     */
    public function getRemainingTrialDaysAttribute(): ?int
    {
        if (!$this->trial_ends_at || $this->trial_ends_at->isPast()) {
            return null;
        }

        return now()->diffInDays($this->trial_ends_at);
    }

    /**
     * Obtenir le nombre de jours restants avant expiration
     */
    public function getRemainingDaysAttribute(): ?int
    {
        if (!$this->ends_at || $this->ends_at->isPast()) {
            return null;
        }

        return now()->diffInDays($this->ends_at);
    }

    /**
     * Obtenir le prix de l'abonnement selon le cycle
     */
    public function getPriceAttribute(): float
    {
        return $this->billing_cycle === self::BILLING_YEARLY 
            ? $this->subscriptionPlan->yearly_price 
            : $this->subscriptionPlan->monthly_price;
    }

    /**
     * Activer l'abonnement après l'essai
     */
    public function activate(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => $this->calculateNextBillingDate(),
        ]);
    }

    /**
     * Annuler l'abonnement
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Suspendre l'abonnement
     */
    public function suspend(): bool
    {
        return $this->update([
            'status' => self::STATUS_SUSPENDED,
        ]);
    }

    /**
     * Marquer comme expiré
     */
    public function expire(): bool
    {
        return $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Renouveler l'abonnement
     */
    public function renew(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => $this->calculateNextBillingDate(),
        ]);
    }

    /**
     * Changer le plan d'abonnement
     */
    public function changePlan(int $newPlanId, string $billingCycle = null): bool
    {
        $data = ['subscription_plan_id' => $newPlanId];
        
        if ($billingCycle) {
            $data['billing_cycle'] = $billingCycle;
        }

        return $this->update($data);
    }

    /**
     * Calculer la prochaine date de facturation
     */
    public function calculateNextBillingDate(): Carbon
    {
        return $this->billing_cycle === self::BILLING_YEARLY
            ? now()->addYear()
            : now()->addMonth();
    }

    /**
     * Vérifier si le tenant peut avoir plus d'employés
     */
    public function canAddEmployee(int $currentEmployeeCount): bool
    {
        $maxEmployees = $this->subscriptionPlan->max_employees;
        
        // Si pas de limite, on peut toujours ajouter
        if (is_null($maxEmployees)) {
            return true;
        }

        return $currentEmployeeCount < $maxEmployees;
    }

    /**
     * Obtenir le statut formaté
     */
    public function getFormattedStatusAttribute(): string
    {
        return match($this->status) {
            self::STATUS_TRIAL => 'Période d\'essai',
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_PAST_DUE => 'Impayé',
            self::STATUS_CANCELLED => 'Annulé',
            self::STATUS_SUSPENDED => 'Suspendu',
            self::STATUS_EXPIRED => 'Expiré',
            default => $this->status,
        };
    }

    /**
     * Obtenir la couleur du badge de statut
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_TRIAL => 'blue',
            self::STATUS_ACTIVE => 'green',
            self::STATUS_PAST_DUE => 'orange',
            self::STATUS_CANCELLED => 'red',
            self::STATUS_SUSPENDED => 'red',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Boot method pour gérer les événements
     */
    protected static function boot()
    {
        parent::boot();

        // Vérifier automatiquement l'expiration
        static::creating(function ($subscription) {
            if ($subscription->status === self::STATUS_TRIAL && !$subscription->trial_ends_at) {
                $subscription->trial_ends_at = $subscription->subscriptionPlan->calculateTrialEndsAt();
            }
        });
    }
}