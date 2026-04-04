<?php

namespace App\Filament\Resources\Transcriptions\Schemas;

use App\Models\TranscriptionState;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class TranscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('description')->label("Beschreibung")->required(),
                Select::make("transcription_state_id")->relationship("transcription_state", "title")->disabledOn(["edit"])->visibleOn("edit"),
                Select::make("user_id")->relationship("user", "name")->disabledOn(["edit"])->visibleOn("edit")->default(Filament::auth()->id()),
                DateTimePicker::make("upload_time")->disabledOn(["edit"])->visibleOn("edit"),
                DateTimePicker::make("start_time")->disabledOn(["edit"])->visibleOn("edit"),
                DateTimePicker::make("end_time")->disabledOn(["edit"])->visibleOn("edit"),
                FileUpload::make('attachment')->storeFileNamesIn('attachment_filename')->disabledOn("edit")->visible(function(Get $get) {
                    $stateDone = TranscriptionState::where("name", "done")->first();
                    return (!$get("transcription_state_id") == $stateDone->id);
                }),
                FileUpload::make('transcription')->visibleOn(["edit"])->disabled()->downloadable()->visible(function(Get $get) {
                    $stateDone = TranscriptionState::where("name", "done")->first();
                    return ($get("transcription_state_id") == $stateDone->id);
                }),
            ]);
    }
}
