<?php

namespace App\Mail\Supplier;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CompleteSupplierRegisterInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;

    public $inviter;

    public $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, string $externalUrl)
    {
        $this->user = $user;
        $this->inviter = auth()->user();
        $this->url = URL::signedExternalRoute(
            $externalUrl,
            'api.v1.supplier.sign-up',
            ['user' => $user->id],
            now()->addDays(2)
        );
        $this->onQueue('notifications');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails/invitation-complete-register.subject', ['app_name' => config('app.name')]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitation-complete-register',
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
