<?php

namespace App\Http\Controllers\Api\V1\Admin\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Transformers\TransactionTransformer;

// __REVIEW__ change filename to FinancingOrderTransactionController
// __REVIEW__ path app/Http/Controllers/Api/V1/Admin/FinancingOrders
class TransactionController extends Controller
{
    public function index(Company $company)
    {
        return fractal($company->transactions()->paginate(), new TransactionTransformer())->respond();
    }
}
