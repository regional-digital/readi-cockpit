<?php

namespace App\Observers;

use App\Models\Transcription;
use Illuminate\Support\Facades\Storage;

class TranscriptionObserver
{
    public function deleting(Transcription $transcription): void
    {
        if($transcription->attachment != null) {
            $basename = basename($transcription->attachment, '.mp3');
            if(Storage::exists($transcription->attachment)) Storage::delete($transcription->attachment);
            if(Storage::exists($basename.'.zip')) Storage::delete($basename.'.zip');
        }
    }
}
