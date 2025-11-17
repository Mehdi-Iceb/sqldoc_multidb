<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'has_trial',
        'trial_days',
        'max_employees',
        'is_active',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'has_trial' => 'boolean',
        'trial_days' => 'integer',
        'max_employees' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec les souscriptions des tenants
     */
    public function tenantSubscriptions()
    {
        return $this->hasMany(TenantSubscription::class);
    }

    /**
     * Obtenir les tenants ayant ce plan
     */
    public function tenants()
    {
        return $this->hasManyThrough(
            Tenant::class,
            TenantSubscription::class,
            'subscription_plan_id',
            'id',
            'id',
            'tenant_id'
        );
    }

    /**
     * Scope pour récupérer uniquement les plans actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Vérifier si le plan a une limite d'employés
     */
    public function hasEmployeeLimit(): bool
    {
        return !is_null($this->max_employees);
    }

    /**
     * Obtenir le prix selon le cycle de facturation
     */
    public function getPriceForCycle(string $cycle): float
    {
        return $cycle === 'yearly' ? $this->yearly_price : $this->monthly_price;
    }

    /**
     * Obtenir le prix avec réduction annuelle en pourcentage
     */
    public function getYearlySavingsPercentageAttribute(): float
    {
        if ($this->monthly_price == 0 || $this->yearly_price == 0) {
            return 0;
        }

        $yearlyFromMonthly = $this->monthly_price * 12;
        return round((($yearlyFromMonthly - $this->yearly_price) / $yearlyFromMonthly) * 100, 2);
    }

    /**
     * Obtenir les économies annuelles en valeur
     */
    public function getYearlySavingsAmountAttribute(): float
    {
        return ($this->monthly_price * 12) - $this->yearly_price;
    }

    /**
     * Vérifier si le plan est gratuit
     */
    public function isFree(): bool
    {
        return $this->monthly_price == 0 && $this->yearly_price == 0;
    }

    /**
     * Vérifier si le plan est "Basic" (ou gratuit)
     */
    public function isBasic(): bool
    {
        return $this->slug === 'basic';
    }

    /**
     * Vérifier si le plan est "Pro"
     */
    public function isPro(): bool
    {
        return $this->slug === 'pro';
    }

    /**
     * Vérifier si le plan est "Enterprise"
     */
    public function isEnterprise(): bool
    {
        return $this->slug === 'enterprise';
    }

    /**
     * Vérifier si le plan est "Private"
     */
    public function isPrivate(): bool
    {
        return $this->slug === 'private';
    }

    /**
     * Obtenir le prix formaté
     */
    public function getFormattedMonthlyPriceAttribute(): string
    {
        return number_format($this->monthly_price, 2, ',', ' ') . ' €';
    }

    public function getFormattedYearlyPriceAttribute(): string
    {
        return number_format($this->yearly_price, 2, ',', ' ') . ' €';
    }

    /**
     * Calculer la date de fin d'essai
     */
    public function calculateTrialEndsAt(): \Carbon\Carbon
    {
        return now()->addDays($this->trial_days);
    }

    /**
     * Obtenir le nombre de tenants actifs sur ce plan
     */
    public function getActiveTenantsCountAttribute(): int
    {
        return $this->tenantSubscriptions()
            ->whereIn('status', ['trial', 'active'])
            ->count();
    }
}