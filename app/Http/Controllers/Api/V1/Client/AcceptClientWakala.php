<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Contracts\Clients\AcceptClientWakala as AcceptWakalaInterface;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\AcceptClientWakalaRequest;
use App\Models\FinancingOrder;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AcceptClientWakala extends Controller
{
    use TraderHelperTrait;

    /**
     * Handle the incoming request.
     *
     * @param  AcceptClientWakalaRequest  $request
     * @param  AcceptWakalaInterface  $acceptClientWakala
     * @return JsonResponse
     */
    public function __invoke(
        AcceptClientWakalaRequest $request,
        AcceptWakalaInterface $acceptClientWakala
    ) {
        return DB::transaction(function () use ($request, $acceptClientWakala) {
            $order = FinancingOrder::lockForUpdate()
                ->findOrFail($request->validated('order_id'));

            $traderOrder = $order->activeTraderOrder()->first();

            $tokenCacheKey = sprintf('client_wakala_token_%s_%s', $order->id, $order->getNationalId());

            $hashedToken = Cache::get($tokenCacheKey);

            if (! Hash::check($request->bearerToken(), $hashedToken)) {
                throw new AuthorizationException();
            }

            $canProceed = $order->getNationalId() === $request->validated('national_id')
                && $traderOrder !== null
                && ! $traderOrder->checkOrderStepComplete(FinancingOrderStatus::ClientWakalaCompleted);

            abort_if(! $canProceed, 404);

            $acceptClientWakala->handle($traderOrder);

            $order->update([
                'status' => FinancingOrderStatus::ClientWakalaCompleted,
            ]);

            Cache::forget($tokenCacheKey);

            return $this->successResponse([
                'wakala_file_url' => route(
                    'api.v1.client.media.download',
                    [
                        'media' => $traderOrder->getFirstMedia(TraderOrderMediaCollection::ClientWakala),
                    ]
                ),
            ]);
        });
    }
}
