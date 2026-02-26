<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Enums\WalletType;
use App\Exports\WalletTransactionsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\ListTransactionRequest;
use App\Jobs\Reports\Enums\ReportType;
use App\Models\Lender;
use App\Services\ExportService;
use App\Support\Wallets\WalletService;

class ExportWalletTransactions extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::LenderTransactions, Action::Index, Action::Show])
        );
    }

    public function __invoke(ListTransactionRequest $request, Lender $lender, ExportService $exportService)
    {
        $walletId = app(WalletService::class)
            ->findByNameOrFail($lender, WalletType::CompanyWallet)
            ->getKey();

        // Dispatch the export job to be processed asynchronously
        $exportService->dispatchTransactionListExportJob(
            exportType: ReportType::TransactionList,
            user: $request->user(),
            exportClass: WalletTransactionsExport::class,
            fileName: $this->getFileName($lender),
            requestData: [...$request->validated(), 'wallet_id' => $walletId, 'lang' => app()->getLocale()],
            locale: app()->currentLocale(),
        );

        // Return a response indicating the export is being processed
        return $this->successResponse([
            'message' => __('notification.report-export-processing'),
        ]);
    }

    private function getFileName(Lender $lender, string $extension = 'xlsx'): string
    {
        $dateTime = saudi_now('Ymd_His');

        return "{$lender->unique_name}_LYNKWalletTrans_{$dateTime}.{$extension}";
    }
}
