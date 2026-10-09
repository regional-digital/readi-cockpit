<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TranscriptionFinished;
use App\Models\Transcription;
use App\Models\TranscriptionState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WhisperJobController extends Controller
{
    /**
     * Hand the oldest waiting transcription to the whisper server and mark it as queued.
     *
     * Transcriptions that stayed queued longer than the configured timeout are handed out again,
     * so a crashed whisper run does not block a job forever.
     */
    public function claim(): JsonResponse|Response
    {
        $stateUploaded = TranscriptionState::where('name', 'uploaded')->firstOrFail();
        $stateQueued = TranscriptionState::where('name', 'queued')->firstOrFail();

        $transcription = DB::transaction(function () use ($stateUploaded, $stateQueued): ?Transcription {
            $transcription = Transcription::query()
                ->whereNotNull('attachment')
                ->where(function ($query) use ($stateUploaded, $stateQueued) {
                    $query->where('transcription_state_id', $stateUploaded->id)
                        ->orWhere(function ($query) use ($stateQueued) {
                            $query->where('transcription_state_id', $stateQueued->id)
                                ->where('updated_at', '<=', now()->subHours((int) config('services.whisper.job_timeout_hours')));
                        });
                })
                ->oldest()
                ->lockForUpdate()
                ->first();

            if ($transcription === null) {
                return null;
            }

            $transcription->transcription_state_id = $stateQueued->id;
            $transcription->touch();

            return $transcription;
        });

        if ($transcription === null) {
            return response()->noContent();
        }

        return response()->json([
            'id' => $transcription->id,
            'name' => pathinfo($transcription->attachment, PATHINFO_FILENAME),
            'audio_url' => route('whisper.jobs.audio', $transcription),
            'result_url' => route('whisper.jobs.result', $transcription),
        ]);
    }

    /**
     * Download the uploaded mp3 of a transcription.
     */
    public function audio(Transcription $transcription): StreamedResponse
    {
        abort_unless($transcription->attachment !== null && Storage::exists($transcription->attachment), Response::HTTP_NOT_FOUND);

        return Storage::download($transcription->attachment, basename($transcription->attachment));
    }

    /**
     * Store the zipped whisper result, mark the transcription as done and inform the user.
     *
     * The zip can be sent as multipart field "result" (curl -F) or as raw request body
     * with Content-Type application/zip or application/octet-stream (wget --post-file).
     */
    public function result(Request $request, Transcription $transcription): JsonResponse
    {
        $stateDone = TranscriptionState::where('name', 'done')->firstOrFail();

        $filename = pathinfo($transcription->attachment, PATHINFO_FILENAME).'.zip';

        if ($request->hasFile('result')) {
            $request->validate(['result' => ['file', 'mimes:zip']]);
            $request->file('result')->storeAs('', $filename);
        } elseif (in_array($request->headers->get('Content-Type'), ['application/zip', 'application/octet-stream'], true) && $request->getContent() !== '') {
            Storage::put($filename, $request->getContent());
        } else {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'No result file given.');
        }

        $transcription->transcription_state_id = $stateDone->id;
        $transcription->transcription = $filename;
        $transcription->save();

        Mail::to($transcription->user)->send(new TranscriptionFinished($transcription, Storage::path($filename)));

        return response()->json(['id' => $transcription->id, 'transcription' => $filename]);
    }
}
