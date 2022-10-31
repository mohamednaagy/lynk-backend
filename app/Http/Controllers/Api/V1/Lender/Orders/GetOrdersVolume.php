<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Enums\DatePeriod;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\OrderVolumeRequest;
use App\Models\FinancingOrder;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

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
        $ordersQuery = FinancingOrder::query()
            ->when(
                $orderVolumeRequest->validated('starting_date'), function ($query) use ($startingDate) {
                    $query->whereDate('created_at', '>=', $startingDate);
                }
            )->when(
                $endingDate, function ($query) use ($endingDate) {
                    $query->whereDate('created_at', '<=', $endingDate);
                }
            );

        switch ($filter) {
            case DatePeriod::YEAR:
                $chart = $this->queryYearlyChart($ordersQuery);
                break;
            case DatePeriod::MONTH:
                $chart = $this->getMonthlyChart($ordersQuery);
                break;

            case DatePeriod::WEEK:
                $chart = $this->queryWeeklyChart($ordersQuery);
                break;
            default:
                $chart = $this->queryYearlyChart($ordersQuery);
                break;
        }

        return $this->successResponse(['AXIS_Y' => array_keys($chart), 'AXIS_X' => array_values($chart)]);
    }

    private function getMonthlyChart($query)
    {
        $ordersQuery = $query->selectRaw('COUNT(*) as count')
            ->selectRaw(
                "DATE_FORMAT(created_at, '%Y-%m') label"
            )
            ->groupBy(
                'label'
            )->pluck(
                'count',
                'label'
            );

        $yearMonth = $this->getPeriodBetween($ordersQuery->keys()->first(), $ordersQuery->keys()->last());
        $chart = array_replace(array_fill_keys($yearMonth, 0), $ordersQuery->toArray());

        return $chart;
    }

    private function queryYearlyChart($query)
    {
        $ordersQuery = $query->selectRaw('COUNT(*) as count')
            ->selectRaw(
                "DATE_FORMAT(created_at, '%Y') label"
            )
            ->groupBy(
                'label'
            )
            ->pluck(
                'count',
                'label'
            );

        $yearMonth = $this->getPeriodBetween($ordersQuery->keys()->first(), $ordersQuery->keys()->last(), 'Y');
        $chart = array_replace(array_fill_keys($yearMonth, 0), $ordersQuery->toArray());

        return $chart;
    }

    private function queryWeeklyChart($query)
    {
        $ordersQuery = $query->selectRaw('COUNT(*) as count')
            ->selectRaw(
                "DATE_FORMAT(created_at, '%Y-%m-%U') as label",
            )
            ->groupBy(
                'label',
            )->pluck(
                'count',
                'label'
            );

        $yearMonth = $this->getPeriodBetween($ordersQuery->keys()->first(), $ordersQuery->keys()->last(), 'weeks');

        $chart = array_replace(array_fill_keys($yearMonth, 0), $ordersQuery->toArray());

        return $chart;
    }

    private function getPeriodBetween($fromYear, $toYear, $format = 'Y-m')
    {
        if ($format == 'weeks') {
            return $this->weeksFormat($fromYear, $toYear);
        }

        $range = CarbonPeriod::create(date($fromYear), date($toYear));
        $months = [];
        foreach ($range as $month) {
            $months[] = $month->format($format);
        }

        return $months;
    }

    public function weeksFormat($from, $to)
    {
        $arrayOfDateFrom = explode('-', $from);
        $arrayOfDateTo = explode('-', $to);
        $yearMonthFrom = $arrayOfDateFrom[0].'-'.$arrayOfDateFrom[1];
        $yearMonthTo = $arrayOfDateTo[0].'-'.$arrayOfDateTo[1];
        $range = CarbonPeriod::create(date($yearMonthFrom), date($yearMonthTo));
        $yearInWeeks = [];
        foreach ($range as $month) {
            $month = $month->format('Y-m-W');
            if (! in_array($month, $yearInWeeks)) {
                $yearInWeeks[] = $month;
            }
        }

        return $yearInWeeks;
    }
}
