<?php

namespace App\Mail;

use App\Enums\CompanyType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CompleteRegisterInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;

    public $inviter;

    public $url;

    public array $registerRouteNamesByCompanyType = [
        CompanyType::Lender => 'api.v1.lender.sign-up',
        CompanyType::Trader => 'api.v1.trader.sign-up',
        CompanyType::Supplier => 'api.v1.supplier.sign-up',
    ];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, string $externalUrl, int $companyType)
    {
        $this->user = $user;
        $this->inviter = auth()->user();
        $this->url = URL::signedExternalRoute(
            $externalUrl,
            $this->registerRouteNamesByCompanyType[$companyType],
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
