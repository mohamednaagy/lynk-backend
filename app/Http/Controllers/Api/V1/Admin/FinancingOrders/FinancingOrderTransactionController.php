<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Transformers\TransactionTransformer;

class FinancingOrderTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderTransactions, Action::Index, Action::Manage])
        )->only('index');
    }

    public function index(Company $company)
    {
        return fractal($company->transactions(WalletType::CompanyWallet)->paginate(), new TransactionTransformer())->respond();
    }
}
