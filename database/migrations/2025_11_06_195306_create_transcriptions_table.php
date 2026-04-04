<?php

use App\Models\TranscriptionState;
use App\Models\User;
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
        Schema::create('transcriptions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("description");
            $table->string("attachment")->nullable();
            $table->string("attachment_filename")->nullable();
            $table->string("transcription")->nullable();
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(TranscriptionState::class);
            $table->datetime("upload_time")->nullable();
            $table->datetime("start_time")->nullable();
            $table->datetime("end_time")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transcriptions');
    }
};
