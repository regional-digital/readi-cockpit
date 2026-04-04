<?php

namespace App\Mail;

use App\Filament\Resources\Transcriptions\TranscriptionResource;
use App\Models\Transcription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TranscriptionFinished extends Mailable
{
    use Queueable, SerializesModels;
    public string $url;

    /**
     * Create a new message instance.
     */
    public function __construct(
         public Transcription $transcription,
    )
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Transcription Finished',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $this->url = TranscriptionResource::getUrl('edit', ['record' => $this->transcription->id]);
        return new Content(
            view: 'mail.transcriptionFinished',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
