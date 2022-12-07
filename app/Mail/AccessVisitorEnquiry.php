<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class AccessVisitorEnquiry extends Mailable
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
            // __REVIEW__ change emails/visitor-enquiry.subject to emails/visitor-enquiry.subject_of_access_to_enquiry
            // __REVIEW__ subject_of_access_to_enquiry should have the following values:
            // __REVIEW__ English: We Received Your Enquiry #XX
            // __REVIEW__ Arabic: وصلنا استفسارك #XX
            // __REVIEW__ app_name should not exist!!!
            subject: __('emails/visitor-enquiry.subject', ['app_name' => config('app.name')]),
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
            markdown: 'emails.visitor-enquiry',
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
