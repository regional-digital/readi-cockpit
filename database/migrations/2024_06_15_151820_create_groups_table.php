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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->string("name");
            $table->string("description")->nullable();
            $table->boolean("moderated")->nullable();
            $table->boolean("has_mailinglist")->nullable();
            $table->boolean("has_keycloakgroup")->nullable();
            $table->string("mailinglisturl")->nullable();
            $table->string("mailinglistpassword")->nullable();
            $table->string("keycloakgroup")->nullable();
            $table->string("keycloakadmingroup")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
