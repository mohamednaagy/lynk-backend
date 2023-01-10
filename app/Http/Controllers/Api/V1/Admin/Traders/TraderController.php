<?php

namespace App\Http\Controllers\Api\V1\Admin\Traders;

use App\Actions\Contracts\Traders\GetPaginatedTraderUsers;
use App\Enums\Area;
use App\Http\Controllers\Controller;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TraderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(GetPaginatedTraderUsers $getPaginatedTraderUsers): JsonResponse
    {
        return fractal($getPaginatedTraderUsers->handle(), new UserTransformer(Area::Trader))
            ->parseIncludes([
                'id',
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'phone_country_code',
                'formatted_phone_number',
                'role',
            ])->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
