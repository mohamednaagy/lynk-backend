<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\GetTransactions;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Exports\WalletTransactionsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\ListTransactionRequest;
use App\Models\Company;
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
        $company = tenant();

        $transactionsQuery = $getTransactions
            ->setCompany($company)
            ->setFilters($data)
            ->handle();

        $export = new WalletTransactionsExport($request, $transactionsQuery, $company);

        return Excel::download($export, $this->getFileName($company, 'csv'), MaatwebsiteExcel::CSV, [
            'X-File-Name' => $this->getFileName($company, 'csv'),
        ]);
    }

    protected function getFileName(Company $company, string $type = 'xlsx')
    {
        $companyName = str_replace(' ', '', $company->name);
        $todayDateInYYYYMMDD = saudi_now('Ymd_His');

        return "{$companyName}_LYNKWalletTransactions_{$todayDateInYYYYMMDD}.{$type}";
    }
}
