<?php

namespace App\Console\Commands;

use App\Filament\Resources\Transcriptions\TranscriptionResource;
use App\Mail\TranscriptionFinished;
use App\Models\Transcription;
use App\Models\TranscriptionState;
use App\Models\User;
use \Illuminate\Support\Facades\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class transcriptionqueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:transcriptionqueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(!Storage::exists('staging')) Storage::makeDirectory('staging');
        if(!Storage::exists('output')) Storage::makeDirectory('output');
        $filenamePrefix = "storage/app/private/";
        $stagingPrefix = "../storage/app/private/staging/";
        $outputPrefix = "../storage/app/private/output/";
        $stateUploaded = TranscriptionState::where("name", "uploaded")->first();
        $stateQueued = TranscriptionState::where("name", "queued")->first();
        $stateDone = TranscriptionState::where("name", "done")->first();
        // Hochgeladene Transcriptionen laden
        $transcriptions = Transcription::where("transcription_state_id", $stateUploaded->id)->get();
        // Dateien ausspielen in Übergabe-Stagingverzeichnis
        // Dateien verschieben in Übergabe-Verzeichnis
        foreach($transcriptions as $transcription) {
            $file = Storage::url($transcription->attachment);
            $filename = $filenamePrefix.basename($file);
            $staging = public_path($stagingPrefix.$transcription->attachment);
            File::copy($filename,$staging);

            $output = public_path($outputPrefix.$transcription->attachment);
            File::move($staging,$output);
            $transcription->transcription_state_id = $stateQueued->id;
            $transcription->save();
        }

        //Eingabe-Verzeichnis laden
        if(!Storage::exists('input')) Storage::makeDirectory('input');
        $inputFiles = Storage::files('input');
        foreach($inputFiles as $inputFile) {
            $filename = basename($inputFile);
            $basename = basename($inputFile, '.zip');
            //Dateien (.zip) den Jobs zuordnen
            $transcription = Transcription::where("attachment", "$basename.mp3")->first();
            if($transcription != null) {
                $destination = $filenamePrefix.$filename;
                File::move($filenamePrefix."/".$inputFile, $destination);

                $transcription->transcription_state_id = $stateDone->id;
                $transcription->transcription = $filename;
                $transcription->save();

                //Mail mit Downloadlink versenden
                $user = User::find($transcription->user_id);
                Mail::to($user)->send(new TranscriptionFinished($transcription));

            }
        }

    }
}
