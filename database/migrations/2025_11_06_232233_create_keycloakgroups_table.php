<?php

use App\Models\Keycloakgroup;
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
        Schema::create('keycloakgroups', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("name");
            $table->string("path");
            $table->string("groupId");
            $table->string("parentId");
            $table->foreignIdFor(Keycloakgroup::class, "parent_id");
            $table->datetime("lastsynctime")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keycloakgroups');
    }
};
