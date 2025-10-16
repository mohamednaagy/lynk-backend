<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Actions\Contracts\Wallets\GetTransactions;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Exports\WalletTransactionsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\ListTransactionRequest;
use App\Models\Company;
use Maatwebsite\Excel\Excel as MaatwebsiteExcel;
use Maatwebsite\Excel\Facades\Excel;

class ExportWalletTransactions extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::LenderTransactions, Action::Index, Action::Show])
        );
    }

    public function __invoke(ListTransactionRequest $request, Company $lender)
    {
        $getTransactions = app(GetTransactions::class);
        $data = $request->validated();
        $transactionsQuery = $getTransactions
            ->setCompany($lender)
            ->setFilters($data)
            ->handle();

        // Generate and download the Excel file
        $export = new WalletTransactionsExport($request, $transactionsQuery, $lender);

        return Excel::download($export, $this->getFileName($lender, 'csv'), MaatwebsiteExcel::CSV, [
            'X-File-Name' => $this->getFileName($lender, 'csv'),
        ]);
    }

    private function getFileName(Company $lender, string $extension = 'xlsx'): string
    {
        $companyName = str_replace(' ', '', $lender->name);
        $dateTime = saudi_now('Ymd_His');

        return "{$companyName}_LYNKWalletTransactions_{$dateTime}.{$extension}";
    }
}
