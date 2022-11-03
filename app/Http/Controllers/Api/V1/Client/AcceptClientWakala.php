<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Contracts\Clients\AcceptClientWakala as AcceptWakalaInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\AcceptClientWakalaRequest;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AcceptClientWakala extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  FinancingOrder  $order
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

            $tokenCacheKey = sprintf('client_wakala_token_%s_%s', $order->id, $order->getNationalId());

            $hashedToken = Cache::get($tokenCacheKey);

            if (! Hash::check($request->bearerToken(), $hashedToken)) {
                throw new AuthorizationException();
            }

            abort_if($order->getNationalId() !== $request->validated('national_id'), 404);

            $media = $acceptClientWakala->handle($order);

            Trader::driver('dmcc')->getTti($order);

            Cache::forget($tokenCacheKey);

            return $this->successResponse([
                'wakala_file_url' => $media->getUrl(),
            ]);
        });
    }
}
