<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ReplyToVisitorEnquiry extends Mailable
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
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails/visitor-enquiry.subject_of_reply_to_enquiry', ['enquiry_id' => $this->enquiry->id]),
        );
    }

    /**
     * Get the message content definition.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.enquiry-reply',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments(): array
    {
        return [];
    }
}
