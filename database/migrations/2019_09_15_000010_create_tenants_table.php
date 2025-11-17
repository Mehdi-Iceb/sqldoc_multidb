<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('slug')->after('name');
            $table->string('contact_name')->after('slug');
            $table->string('contact_email')->after('contact_name');
            $table->string('type')->default('pro')->after('contact_email');
            $table->string('country', 2)->default('FR')->after('type');
            $table->string('industry')->nullable()->after('country');
            $table->text('logo')->nullable();
            $table->string('address')->nullable()->after('industry');
            $table->string('postalcode')->nullable()->after('address');
            $table->string('city')->nullable()->after('postalcode');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'slug', 'contact_name', 'contact_email', 
                'type', 'country', 'industry', 'address', 
                'postalcode', 'city'
            ]);
        });
    }
};