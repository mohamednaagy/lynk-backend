<?php

namespace App\Http\Controllers\Api\V1\Edaat;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Models\EdaatInvoice;
use App\Support\Edaat\EdaatService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __invoke(
        Request $request,
        EdaatService $edaatService,
        CreateTransactions $createTransactions,
        string $status
    ) {
        foreach ($request->all() as $invoice) {
            if ($edaatService->isPaidInvoice($invoice['InvoiceNo'])) {
                $invoice = EdaatInvoice::where('id', $invoice['InternalCode'])->first();
                $wallet = $invoice->company->getWallet(WalletType::CompanyWallet);
                $invoice->update(['status' => EdaatInvoiceStatus::Paid]);
                $createTransactions->handle($wallet, TransactionReason::DepositByEdaat, $invoice->amount, []);
            }
        }
    }
}
