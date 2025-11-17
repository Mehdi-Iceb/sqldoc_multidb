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
        Schema::create('trigger_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_trigger')->nullable()->constrained('trigger_description');
            $table->string('table', 255)->nullable();
            $table->string('type', 10)->nullable();
            $table->string('event', 25)->nullable();
            $table->tinyInteger('state')->nullable();
            $table->string('schema', 250)->nullable();
            $table->tinyInteger('is_disabled')->nullable();
            $table->dateTime2('creation_date')->nullable();
            $table->dateTime2('last_change_date')->nullable();
            $table->text('definition')->nullable();
            $table->dateTime2('created_at')->nullable();
            $table->dateTime2('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trigger_information');
    }
};
