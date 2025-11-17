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
        Schema::create('ps_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ps')->nullable()->constrained('ps_description');
            $table->string('schema', 10)->nullable();
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
        Schema::dropIfExists('ps_information');
    }
};
