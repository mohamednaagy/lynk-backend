<?php

namespace App\Jobs;

use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\SoapClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDMCCNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected SoapClient $soap;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->soap = Soap::buildClient('dmcc');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('notificationDetailsRequest'))
            ->call('notificationDetailsRequest', [
                'notificationType' => 'ACTIONABLE',
            ]);

        collect($response->object()
            ->NotificationAllDetailsResponse[0]
            ->notificationAllDetailsResponse
            ->notificationDetails
        )->each(function ($notification) {
            if (
                $notification->notificationHeaderAndEntity->notification
                ==
                'Action Required for Promise to Purchase'
            ) {
                ProcessDMCCPTPNotification::dispatch($notification);
            } elseif (
                $notification->notificationHeaderAndEntity->notification
                ==
                'Action Required for Issue Murabaha Purchase Offer'
            ) {
                ProcessDMCCMPONotification::dispatch($notification);
            }
        });
    }

    private function prefixUrl($url): string
    {
        return 'https://'.config('dmcc.username').':'.config('dmcc.password').'@na2.ai.dm-us.informaticacloud.com/active-bpel/soap/'.$url.'?wsdl';
    }
}
