<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\UpdateMurabhaCompleteDocument;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Http\Request;

class UpdateMurabhaCompleteDocumentAction implements UpdateMurabhaCompleteDocument
{
    use TraderHelperTrait;

    /**
     * @param  Request  $request
     * @param  TraderOrder  $traderOrder
     * @return void
     */
    public function handle(Request $request, TraderOrder $traderOrder): void
    {
        $this->attachDocumentToOrder(
            $traderOrder,
            base64_encode(file_get_contents($request->file('document'))),
            TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
            'base64'
        );
    }
}
