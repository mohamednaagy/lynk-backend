<?php

namespace App\Mail\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CompleteAdminRegisterInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, string $externalUrl)
    {
        // __REVIEW__ move $externalUrl to be on a new line
        // __REVIEW__ put expiry time 48 hours on the signed url
        $url = URL::signedExternalRoute($externalUrl,
            'admin.complete-register',
            ['admin' => $user->id]
        );

        $this->url = $url;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        // __REVIEW__ subject translation is not working
        // __REVIEW__ use different file for admin invitation
        return new Envelope(
            subject: __('emails.invitation-complete-register.complete_registration', ['app_name' => config('app.name')]),
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        // __REVIEW__ use different template for admin invitation
        return new Content(
            markdown: 'emails.invitation-complete-register',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
