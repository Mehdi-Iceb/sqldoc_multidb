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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Basic, Pro, Enterprise, private
            $table->string('slug')->unique(); // basic, pro, enterprise, private
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 8, 2)->default(0);
            $table->decimal('yearly_price', 8, 2)->default(0);
            $table->boolean('has_trial')->default(true);
            $table->integer('trial_days')->default(14);
            $table->integer('max_employees')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
