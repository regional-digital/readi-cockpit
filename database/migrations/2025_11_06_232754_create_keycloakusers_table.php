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
        Schema::create('keycloakusers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("keycloak_id");
            $table->string("username");
            $table->string("email");
            $table->string("email_original");
            $table->datetime("lastsynctime")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keycloakusers');
    }
};
