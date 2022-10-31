<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrdersVolume;
use App\Enums\DatePeriod;
use App\Models\FinancingOrder;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;

class GetOrdersVolumeAction implements GetOrdersVolume
{
    /**
     * @param  array  $data
     * @return mixed
     */
    public function handle(array $data = [])
    {
        $period = Arr::get($data, 'period') ?: DatePeriod::YEAR;
        $startingDate = Arr::get($data, 'starting_date');
        $endingDate = Arr::get($data, 'ending_date');

        $orders = FinancingOrder::query()
            ->when(
                $startingDate, function ($query) use ($startingDate) {
                    $query->whereDate('created_at', '>=', $startingDate);
                }
            )->when(
                $endingDate, function ($query) use ($endingDate) {
                    $query->whereDate('created_at', '<=', $endingDate);
                }
            )
            ->when(
                $period == DatePeriod::YEAR, function ($query) {
                    $query->selectRaw("DATE_FORMAT(created_at, '%Y') as label");
                }
            )
            ->when(
                $period == DatePeriod::MONTH, function ($query) {
                    $query->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as label");
                }
            )
            ->when(
                $period == DatePeriod::WEEK, function ($query) {
                    $query->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%U') as label");
                }
            )
            ->selectRaw(
                'COUNT(*) as count'
            )
            ->groupBy(
                'label'
            )
            ->pluck(
                'count',
                'label'
            );

        if ($orders->isEmpty()) {
            return [];
        }

        $period = $this->getPeriodBetween(
            $orders->keys()->first(),
            $orders->keys()->last(),
            $this->getFormatByPeriod($period)
        );

        return array_replace(array_fill_keys($period, 0), $orders->toArray());
    }

    /**
     * get period between two dates
     *
     * @param  mixed  $fromYear
     * @param  mixed  $toYear
     * @param  mixed  $format
     * @return array<string>
     */
    protected function getPeriodBetween($fromYear, $toYear, $format = DatePeriod::YEAR)
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

    /**
     * get weeks between two dates
     *
     * @param  mixed  $from
     * @param  mixed  $to
     * @return array<string>
     */
    protected function weeksFormat($from, $to)
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

    /**
     * determine format by period
     *
     * @param  mixed  $period
     * @return string
     */
    protected function getFormatByPeriod($period)
    {
        switch ($period) {
            case DatePeriod::WEEK:
                $format = 'weeks';
                break;
            case DatePeriod::MONTH:
                $format = 'Y-m';
                break;
            case DatePeriod::YEAR:
                $format = 'Y';
                break;

            default:
                $format = DatePeriod::YEAR;
                break;
        }

        return $format;
    }
}
