<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasFactory, HasDatabase, HasDomains;

    protected $table = 'tenants';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'contact_name',
        'contact_email',
        'type',
        'country',
        'industry',
        'logo',
        'address',
        'postalcode',  
        'city',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * IMPORTANT : Cette méthode indique à Stancl quels champs ont leur propre colonne
     * Sans cette méthode, tous les champs vont dans la colonne JSON 'data'
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'contact_name',
            'contact_email',
            'type',
            'country',
            'industry',
            'logo',
            'address',
            'postalcode',
            'city',
        ];
    }

    // Relation avec les domaines (table domains)
    public function domains()
    {
        return $this->hasMany(Domain::class, 'tenant_id', 'id');
    }

    // Méthodes utilitaires
    public function isProfessional(): bool
    {
        return $this->type === 'pro';
    }

    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    public function getData(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    public function setData(string $key, $value): void
    {
        $data = $this->data ?? [];
        data_set($data, $key, $value);
        $this->data = $data;
    }

    // Scopes
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('contact_name', 'like', "%{$search}%")
              ->orWhere('contact_email', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%");
        });
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeProfessional($query)
    {
        return $query->where('type', 'pro');
    }

    public function scopePrivate($query)
    {
        return $query->where('type', 'private');
    }

    public function scopeCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    // Méthodes Tenancy

    public function getDatabaseName(): string
    {
        $cleanSlug = strtolower(\Illuminate\Support\Str::slug($this->slug, '_'));
        return 'tenant_' . $cleanSlug;
    }

    public function getDatabaseConnectionName(): string
    {
        $cleanSlug = strtolower(\Illuminate\Support\Str::slug($this->slug, '_'));
        return 'tenant_' . $cleanSlug;
    }

    public function getDatabaseConnectionConfiguration(string $connectionName = null): array
    {
        $connectionName = $connectionName ?? $this->getDatabaseConnectionName();
        $baseConfig = config('database.connections.sqlsrv');
        
        return array_merge($baseConfig, [
            'database' => $this->getDatabaseName(),
        ]);
    }

    public function run(callable $callback)
    {
        $originalConnection = config('database.default');
        $tenantConnection = $this->getDatabaseConnectionName();
        
        config([
            "database.connections.{$tenantConnection}" => $this->getDatabaseConnectionConfiguration($tenantConnection),
            'database.default' => $tenantConnection,
        ]);
        
        try {
            return $callback();
        } finally {
            config(['database.default' => $originalConnection]);
        }
    }
}