<?php

namespace App\Http\Controllers\Api\V1\Supplier\Constant;

use App\Actions\Contracts\GetConstantApi;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Constant\ListConstantRequest;
use Illuminate\Http\JsonResponse;

class ConstantController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::ConstantApi, Action::Index, Action::Manage])
        )->only('index');

    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(ListConstantRequest $request, GetConstantApi $getConstantData)
    {
        $data = $request->validated();

        return $getConstantData->handle($data);

    }
}
