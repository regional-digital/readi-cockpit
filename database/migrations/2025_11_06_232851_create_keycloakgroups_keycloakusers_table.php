<?php

use App\Models\Keycloakgroup;
use App\Models\Keycloakuser;
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
        Schema::create('keycloakgroups_keycloakusers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Keycloakgroup::class);
            $table->foreignIdFor(Keycloakuser::class);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keycloakgroups_keycloakusers', function (Blueprint $table) {
            //
        });
    }
};
