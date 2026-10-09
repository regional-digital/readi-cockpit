<?php

use App\Http\Controllers\Api\WhisperJobController;
use App\Http\Middleware\EnsureWhisperApiToken;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureWhisperApiToken::class)->prefix('whisper/jobs')->name('whisper.jobs.')->group(function () {
    Route::post('next', [WhisperJobController::class, 'claim'])->name('claim');
    Route::get('{transcription}/audio', [WhisperJobController::class, 'audio'])->name('audio');
    Route::post('{transcription}/result', [WhisperJobController::class, 'result'])->name('result');
});
