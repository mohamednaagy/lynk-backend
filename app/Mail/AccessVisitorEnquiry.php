<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class AccessVisitorEnquiry extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $enquiry;

    public $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Enquiry $enquiry, string $externalUrl)
    {
        $this->enquiry = $enquiry;
        $this->url = URL::signedExternalRoute($externalUrl, 'api.v1.visitor.enquiry', ['enquiry' => $enquiry->id]);
        $this->onQueue('notifications');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails/visitor-enquiry.subject_of_access_to_enquiry', ['enquiry_id' => $this->enquiry->id]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.visitor-enquiry',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
