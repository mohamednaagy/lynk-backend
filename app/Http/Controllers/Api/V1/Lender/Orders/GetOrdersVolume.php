<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Enums\DatePeriod;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\OrderVolumeRequest;
use App\Models\FinancingOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GetOrdersVolume extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(OrderVolumeRequest $orderVolumeRequest)
    {
        $filter = $orderVolumeRequest->validated('filter') ?: DatePeriod::YEAR;
        $startingDate = $orderVolumeRequest->validated('starting_date');
        $endingDate = $orderVolumeRequest->validated('ending_date');
        $orders = FinancingOrder::query()
            ->when(
                $orderVolumeRequest->validated('starting_date'), function ($query) use ($startingDate) {
                    $query->whereDate('created_at', '>=', $startingDate);
                }
            )->when(
                $endingDate, function ($query) use ($endingDate) {
                    $query->whereDate('created_at', '<=', $endingDate);
                }
            )->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('Month(created_at) as month_name'),
            )->groupBy(
                'month_name'
            )
            ->pluck(
                'count',
                'month_name'
            );
        $dates = range(1, 12);
        $new = [];
        foreach ($dates as $index => $value) {
            $new[$value] = 0;
            if (isset($orders[$value])) {
                $new[$value] = $orders[$value];
            }
            // code...
        }

        return $new;

        return $orders;
        switch ($filter) {
            case DatePeriod::YEAR:
                $orders = $this->queryFilterByYear($orders);
                break;
            case DatePeriod::MONTH:
                // code...
                break;

            case DatePeriod::WEEK:
                // code...
                break;
            default:
                // code...
                break;
        }

        return $orders->get();
        // ->select(
        //     DB::raw("COUNT(*) as count"),
        //     DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') new_date_formate"),
        //     DB::raw("Year(created_at) as year"),
        //     DB::raw("MONTHNAME(created_at) as month_name"),
        //     DB::raw('WEEK(created_at) as week'),
        // )

        // ->orderBy(
        //     'year'
        // )
        // ->groupBy(
        //     [
        //         'new_date_formate',
        //         'year',
        //         'month_name',
        //         'week'
        //     ]
        // )
        // ->get(
        // );

        return $orders;

        // $orders = FinancingOrder::select(
        //     DB::raw("COUNT(*) as count"),
        //     DB::raw("Year(created_at) as year"),
        //     DB::raw("MONTHNAME(created_at) as month_name"),
        //       DB::raw('WEEK(created_at) as week'),
        // )->groupBy(
        //         'year',
        //         'month_name',
        //         'week'
        //     )->get(
        //     );

        // return $orders;
    }

    private function queryFilterByYear($query)
    {
        return $query->select(
            DB::raw('COUNT(*) as count'),
            DB::raw('Year(created_at) as year'),
            DB::raw('MONTHNAME(created_at) as month_name'),
            DB::raw('WEEK(created_at) as week'),
        )->groupBy(
            'year',
            'month_name',
            'week'
        );
    }
}
