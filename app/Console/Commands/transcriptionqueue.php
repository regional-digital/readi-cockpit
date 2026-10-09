<?php

namespace App\Console\Commands;

use App\Models\Transcription;
use App\Models\TranscriptionState;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Laravel\Prompts\Output\ConsoleOutput;

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
    protected $description = 'Delete finished transcriptions after KEEP_TRANSCRIPTIONS_DAYS (transcribing itself is done by the whisper server via API)';

    private ConsoleOutput $consoleOutput;

    public function __construct()
    {
        parent::__construct();
        $this->consoleOutput = new ConsoleOutput;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stateDone = TranscriptionState::where('name', 'done')->first();

        // Delete transcriptions done longer than 7 days ago
        $transcriptions = Transcription::where('transcription_state_id', $stateDone->id)->whereDate('updated_at', '<=', Carbon::now()->subDays(env('KEEP_TRANSCRIPTIONS_DAYS', '7')))->get();
        foreach ($transcriptions as $transcription) {
            $this->consoleOutput->writeln('<info>Delete transcription with id '.$transcription->id.'</info>');
            $transcription->delete();
        }
    }
}
