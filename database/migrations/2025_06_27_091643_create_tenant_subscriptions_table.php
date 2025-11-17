<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            $table->enum('billing_cycle', ['monthly', 'yearly']);
            $table->datetime('starts_at');
            $table->datetime('ends_at')->nullable();
            $table->datetime('trial_ends_at')->nullable();
            $table->enum('status', [
                'trial',           // Période d'essai
                'active',          // Actif et payé
                'past_due',        // Impayé mais encore actif
                'cancelled',       // Annulé par le client
                'suspended',       // Suspendu par nous
                'expired'          // Expiré
            ])->default('trial');
            $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['status', 'ends_at']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
