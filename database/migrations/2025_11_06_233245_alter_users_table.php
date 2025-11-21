<?php

use App\Models\Keycloakuser;
use App\Models\Mailinglistuser;
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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(Mailinglistuser::class)->nullable();
            $table->foreignIdFor(Keycloakuser::class)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('mailinglistuser_id');
            $table->dropColumn('keycloakuser_id');
        });
    }
};
