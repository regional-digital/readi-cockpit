<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TranscriptionStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('transcription_states')->insert([
            'name' => 'uploaded',
            'title' => 'hochgeladen'
        ]);
        DB::table('transcription_states')->insert([
            'name' => 'queued',
            'title' => 'geplant'
        ]);
        DB::table('transcription_states')->insert([
            'name' => 'done',
            'title' => 'erledigt'
        ]);

    }
}
