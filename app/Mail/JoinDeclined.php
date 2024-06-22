<?php

namespace App\Mail;

use App\Filament\Resources\GroupResource;
use App\Models\Groupmember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JoinDeclined extends Mailable
{
    use Queueable, SerializesModels;

    public Groupmember $groupmember;
    public String $url;

    /**
     * Create a new message instance.
     */
    public function __construct(Groupmember $groupmember)
    {
        $this->groupmember = $groupmember;
        $this->url = GroupResource::getUrl().'/'.$this->groupmember->group->id;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Beitritt zur Gruppe abgelehnt',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.joindeclined',
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
