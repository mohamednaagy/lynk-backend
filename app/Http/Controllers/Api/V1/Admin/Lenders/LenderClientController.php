<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Actions\Contracts\Companies\LenderClients\CreateLenderClient;
use App\Actions\Contracts\Companies\LenderClients\DeleteLenderClient;
use App\Actions\Contracts\Companies\LenderClients\GetPaginatedLenderClients;
use App\Actions\Contracts\Companies\LenderClients\UpdateLenderClient;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\LenderClients\DeleteLenderClientRequest;
use App\Http\Requests\V1\Admin\Companies\LenderClients\StoreLenderClientRequest;
use App\Http\Requests\V1\Admin\Companies\LenderClients\UpdateLenderClientRequest;
use App\Models\Company;
use App\Models\CompanyLenderClient;
use App\Models\Lender;
use App\Transformers\CompanyLenderClientTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LenderClientController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderClients, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderClients, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderClients, Action::Create, Action::Manage])
        )->only('store');

    }

    public function index(
        Company $lender,
        GetPaginatedLenderClients $getPaginatedLenderClients
    ): JsonResponse {
        $clients = $getPaginatedLenderClients->handle($lender);

        return fractal($clients, new CompanyLenderClientTransformer)
            ->parseIncludes([
                'id',
                'name',
                'type',
                'national_id',
            ])
            ->respond();
    }

    public function show(Company $lender, CompanyLenderClient $client): JsonResponse
    {
        return fractal($client, new CompanyLenderClientTransformer)
            ->parseIncludes([
                'name',
                'type',
                'national_id',
                'auto_complete_sell',
                'auto_sell_periods',
            ])
            ->respond();
    }

    public function store(
        Company $lender,
        StoreLenderClientRequest $request,
        CreateLenderClient $createLenderClient,
    ): JsonResponse {
        $data = $request->validated();

        return DB::transaction(function () use ($lender, $data, $createLenderClient) {
            $company = $createLenderClient->handle($lender, $data);

            return fractal($company, new CompanyLenderClientTransformer)
                ->parseIncludes([
                    'name',
                    'type',
                    'national_id',
                    'auto_complete_sell',
                    'auto_sell_periods',
                ])
                ->respond();
        });
    }

    public function update(
        Lender $lender,
        CompanyLenderClient $client,
        UpdateLenderClientRequest $request,
        UpdateLenderClient $updateLenderClient,
    ): JsonResponse {
        $data = $request->validated();

        return DB::transaction(function () use ($client, $data, $updateLenderClient) {
            $client = $updateLenderClient->handle($client, $data);

            return fractal($client->refresh(), new CompanyLenderClientTransformer)
                ->parseIncludes([
                    'name',
                    'type',
                    'national_id',
                    'auto_complete_sell',
                    'auto_sell_periods',
                ])
                ->respond();
        });
    }

    public function destroy(
        Lender $lender,
        CompanyLenderClient $client,
        DeleteLenderClientRequest $request,
        DeleteLenderClient $deleteLenderClient,
    ): JsonResponse {
        $request->validated();

        return DB::transaction(function () use ($client, $deleteLenderClient) {
            $deleteLenderClient->handle($client);

            return $this->successResponse();
        });

    }
}
