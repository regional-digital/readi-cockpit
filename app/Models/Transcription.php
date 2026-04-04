<?php

namespace App\Models;

use App\Observers\TranscriptionObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([TranscriptionObserver::class])]
class Transcription extends Model
{
    protected $fillable = [
        "description"
        , "attachment"
        , "attachment_filename"
        , "transcription"
        , "user_id"
        , "upload_time"
        , "start_time"
        , "end_time"
        , "transcription_state_id"
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transcription_state(): BelongsTo
    {
        return $this->belongsTo(TranscriptionState::class);
    }
}
