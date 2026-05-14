<?php

namespace App\Filament\Resources\Transcriptions\Pages;

use App\Filament\Resources\Transcriptions\TranscriptionResource;
use App\Models\TranscriptionState;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateTranscription extends CreateRecord
{
    protected static string $resource = TranscriptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Filament::auth()->id();
        $data['transcription_state_id'] = TranscriptionState::where("name", "=", "uploaded")->first()->id();
        return $data;
    }

}
