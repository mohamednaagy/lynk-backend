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

    public $invitee;

    public $url;

    public $inviter;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $inviter, User $invitee, string $externalUrl)
    {
        $url = URL::signedExternalRoute(
            $externalUrl,
            'api.v1.admins.admin.sign-up',
            ['admin' => $invitee->id],
            now()->addHours(48)
        );

        $this->url = $url;
        $this->invitee = $invitee;
        $this->inviter = $inviter;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: __(
                'emails/admin-invitation.subject',
                [
                    'app_name' => config('app.name'),
                ]
            ),
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            markdown: 'emails.admin-invitation',
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
