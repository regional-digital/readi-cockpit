<?php

namespace Tests\Feature;

use App\Mail\TranscriptionFinished;
use App\Models\Transcription;
use App\Models\TranscriptionState;
use Database\Seeders\TranscriptionStateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhisperJobApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TranscriptionStateSeeder::class);
        config(['services.whisper.token' => 'secret-token']);
        Storage::fake('local');
        Mail::fake();
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer secret-token', 'Accept' => 'application/json'];
    }

    private function stateId(string $name): int
    {
        return TranscriptionState::where('name', $name)->value('id');
    }

    public function test_requests_without_valid_token_are_rejected(): void
    {
        $this->postJson('/api/whisper/jobs/next')->assertUnauthorized();
        $this->postJson('/api/whisper/jobs/next', [], ['Authorization' => 'Bearer wrong'])->assertUnauthorized();
    }

    public function test_requests_are_rejected_when_no_token_is_configured(): void
    {
        config(['services.whisper.token' => null]);

        $this->postJson('/api/whisper/jobs/next', [], ['Authorization' => 'Bearer '])->assertUnauthorized();
    }

    public function test_claim_returns_no_content_when_nothing_is_waiting(): void
    {
        $this->postJson('/api/whisper/jobs/next', [], $this->authHeaders())->assertNoContent();
    }

    public function test_claim_hands_out_oldest_uploaded_transcription_and_marks_it_queued(): void
    {
        $oldest = Transcription::factory()->create(['attachment' => 'first.mp3', 'created_at' => now()->subHour()]);
        Transcription::factory()->create();

        $this->postJson('/api/whisper/jobs/next', [], $this->authHeaders())
            ->assertOk()
            ->assertExactJson([
                'id' => $oldest->id,
                'name' => 'first',
                'audio_url' => route('whisper.jobs.audio', $oldest),
                'result_url' => route('whisper.jobs.result', $oldest),
            ]);

        $this->assertSame($this->stateId('queued'), $oldest->fresh()->transcription_state_id);
    }

    public function test_claim_skips_recently_queued_but_reclaims_stale_transcriptions(): void
    {
        Transcription::factory()->queued()->create();
        $stale = Transcription::factory()->queued()->create();
        $stale->forceFill(['updated_at' => now()->subHours(13)])->saveQuietly();

        $this->postJson('/api/whisper/jobs/next', [], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('id', $stale->id);

        $this->postJson('/api/whisper/jobs/next', [], $this->authHeaders())->assertNoContent();
    }

    public function test_audio_downloads_the_uploaded_mp3(): void
    {
        $transcription = Transcription::factory()->create(['attachment' => 'audio.mp3']);
        Storage::put('audio.mp3', 'mp3-content');

        $response = $this->get('/api/whisper/jobs/'.$transcription->id.'/audio', $this->authHeaders());

        $response->assertOk();
        $this->assertSame('mp3-content', $response->streamedContent());
    }

    public function test_audio_returns_not_found_when_file_is_missing(): void
    {
        $transcription = Transcription::factory()->create();

        $this->getJson('/api/whisper/jobs/'.$transcription->id.'/audio', $this->authHeaders())->assertNotFound();
    }

    public function test_result_upload_stores_zip_marks_done_and_mails_user(): void
    {
        $transcription = Transcription::factory()->queued()->create(['attachment' => 'audio.mp3']);

        $this->post('/api/whisper/jobs/'.$transcription->id.'/result', [
            'result' => UploadedFile::fake()->createWithContent('audio.zip', $this->zipContent()),
        ], $this->authHeaders())->assertOk();

        Storage::assertExists('audio.zip');
        $transcription->refresh();
        $this->assertSame('audio.zip', $transcription->transcription);
        $this->assertSame($this->stateId('done'), $transcription->transcription_state_id);
        Mail::assertSent(TranscriptionFinished::class, fn (TranscriptionFinished $mail) => $mail->hasTo($transcription->user->email));
    }

    public function test_result_can_be_uploaded_as_raw_body(): void
    {
        $transcription = Transcription::factory()->queued()->create(['attachment' => 'audio.mp3']);

        $this->call('POST', '/api/whisper/jobs/'.$transcription->id.'/result', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer secret-token',
            'CONTENT_TYPE' => 'application/zip',
        ], 'zip-bytes')->assertOk();

        $this->assertSame('zip-bytes', Storage::get('audio.zip'));
        $this->assertSame($this->stateId('done'), $transcription->fresh()->transcription_state_id);
    }

    public function test_result_without_file_is_rejected(): void
    {
        $transcription = Transcription::factory()->queued()->create();

        $this->postJson('/api/whisper/jobs/'.$transcription->id.'/result', [], $this->authHeaders())->assertUnprocessable();

        $this->assertSame($this->stateId('queued'), $transcription->fresh()->transcription_state_id);
        Mail::assertNothingSent();
    }

    private function zipContent(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('audio.txt', 'Hallo Welt');
        $zip->close();

        $content = file_get_contents($path);
        unlink($path);

        return $content;
    }
}
