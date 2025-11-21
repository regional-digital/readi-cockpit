<?php

use App\Models\Mailinglist;
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
        Schema::create('mailinglistusers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("email");
            $table->string("email_original");
            $table->foreignIdFor(Mailinglist::class);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailinglistusers');
    }
};
