<?php

namespace App\Jobs;

use App\Models\TraderHistory;
use App\Models\TraderOrder;
use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\SoapClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessDMCCPTPNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $notification;

    protected SoapClient $soap;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        $this->notification = $notification;
        $this->soap = Soap::buildClient('dmcc');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $ttiId = $this->notification->notificationHeaderAndEntity->notificationEntityDetails->notificationEntity[0]->entityValue;

        $response = $this->soap
            ->baseWsdl($this->prefixUrl('respondPTPService'))
            ->call('respondPTPService', [
                'ttiId' => $ttiId,
                'comments' => 'create PTP',
                'submitAction' => 'true',
            ]);

        if ($response->object()->statusCode == '0000') {
            // request PTP document
            $response = $this->soap
                ->baseWsdl($this->prefixUrl('getDocumentByTypeAndTransaction'))
                ->call('getDocumentByTypeAndTransaction', [
                    'ttiId' => $ttiId,
                    'documentType' => 'Promise to Purchase',
                ]);

            // store in PTP path with ttiId filename
            Storage::put(
                'PTP/'.$ttiId.'.pdf',
                base64_decode($response->object()->getdocument[0]->getDocumentByTypeResponse[0]->document)
            );

            $traderOrder = TraderOrder::query()
                ->where('type', 'TTIID')
                ->where('reference', $ttiId)
                ->first();

            if ($traderOrder) {
                TraderHistory::query()->create([
                    'trader_order_id' => $traderOrder->id,
                    'action' => 'response PTP and store document',
                ]);
            }
        }
    }

    private function prefixUrl($url): string
    {
        return 'https://'.config('dmcc.username').':'.config('dmcc.password').'@na2.ai.dm-us.informaticacloud.com/active-bpel/soap/'.$url.'?wsdl';
    }
}
