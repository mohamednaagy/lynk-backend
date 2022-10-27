<?php

namespace App\Http\Controllers\Api\V1\Admin\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Transformers\TransactionTransformer;

class TransactionController extends Controller
{
    public function index(Company $company)
    {
        return fractal($company->transactions()->paginate(), new TransactionTransformer())->respond();
    }
}
