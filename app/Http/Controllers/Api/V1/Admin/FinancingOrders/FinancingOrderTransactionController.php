<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Transformers\TransactionTransformer;

class FinancingOrderTransactionController extends Controller
{
    public function index(Company $company)
    {
        return fractal($company->transactions()->paginate(), new TransactionTransformer())->respond();
    }
}
