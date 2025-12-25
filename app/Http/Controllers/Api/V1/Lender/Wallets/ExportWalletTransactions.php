<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\GetTransactions;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Exports\WalletTransactionsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\ListTransactionRequest;
use App\Models\Lender;
use Maatwebsite\Excel\Excel as MaatwebsiteExcel;
use Maatwebsite\Excel\Facades\Excel;

class ExportWalletTransactions extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::Lender, [Subject::LenderTransactions, Action::Index])
        );
    }

    public function __invoke(
        ListTransactionRequest $request,
        GetTransactions $getTransactions
    ) {
        $data = $request->validated();
        $lender = tenant();

        $transactionsQuery = $getTransactions
            ->setLender($lender)
            ->setFilters($data)
            ->handle();

        $export = new WalletTransactionsExport($request, $transactionsQuery, $lender);

        return Excel::download($export, $this->getFileName($lender, 'csv'), MaatwebsiteExcel::CSV, [
            'X-File-Name' => $this->getFileName($lender, 'csv'),
        ]);
    }

    protected function getFileName(Lender $lender, string $type = 'xlsx')
    {
        $lenderName = str_replace(' ', '', $lender->name);
        $todayDateInYYYYMMDD = saudi_now('Ymd_His');

        return "{$lenderName}_LYNKWalletTransactions_{$todayDateInYYYYMMDD}.{$type}";
    }
}
