<?php

namespace Database\Factories;

use App\Models\Transcription;
use App\Models\TranscriptionState;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transcription>
 */
class TranscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(),
            'attachment' => fake()->unique()->uuid().'.mp3',
            'attachment_filename' => fake()->word().'.mp3',
            'user_id' => User::factory(),
            'transcription_state_id' => fn () => TranscriptionState::where('name', 'uploaded')->value('id'),
        ];
    }

    /**
     * Indicate that the transcription has been handed to the whisper server.
     */
    public function queued(): static
    {
        return $this->state(fn () => [
            'transcription_state_id' => TranscriptionState::where('name', 'queued')->value('id'),
        ]);
    }
}
