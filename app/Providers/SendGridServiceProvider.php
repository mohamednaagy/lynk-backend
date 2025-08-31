<?php

namespace App\Providers;

use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class SendGridServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerSendGridTransport();
    }

    /**
     * Register SendGrid mail transport.
     */
    private function registerSendGridTransport(): void
    {
        $this->app->make(MailManager::class)->extend('sendgrid', function (array $config) {
            $dsn = new Dsn(
                'sendgrid+api',
                'default',
                $config['key']
            );

            return (new SendgridTransportFactory)->create($dsn);
        });
    }
}
