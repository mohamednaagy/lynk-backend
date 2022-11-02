<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrdersVolume;
use App\Enums\DatePeriod;
use App\Models\FinancingOrder;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Arr;

class GetOrdersVolumeAction implements GetOrdersVolume
{
    const FORMAT_WEEKS = 'weeks';

    const FORMAT_MONTH = 'Y-m';

    const FORMAT_YEAR = 'Y';

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
            ->selectRaw(
                'COUNT(*) as orders_total'
            )
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
            ->groupBy(
                'label'
            )
            ->pluck(
                'orders_total',
                'label'
            );

        if ($orders->isEmpty()) {
            return [];
        }

        // get all periods even if not contain values
        $period = $this->getPeriodBetween(
            $orders->keys()->first(),
            $orders->keys()->last(),
            $this->getFormatByPeriod($period)
        );

        // fill empty periods with 0
        return array_replace(array_fill_keys($period, 0), $orders->toArray());
    }

    /**
     * get period between two dates depend on the format
     *
     * @param  mixed  $fromYear
     * @param  mixed  $toYear
     * @param  mixed  $format
     * @return array<string>
     */
    protected function getPeriodBetween($fromYear, $toYear, $format)
    {
        if ($format == self::FORMAT_WEEKS) {
            return $this->weeksFormat($fromYear, $toYear);
        }

        $range = CarbonPeriod::create(date($fromYear), date($toYear));
        $periods = [];
        foreach ($range as $month) {
            $periods[] = $month->format($format);
        }

        return $periods;
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
                $format = self::FORMAT_WEEKS;
                break;

            case DatePeriod::MONTH:
                $format = self::FORMAT_MONTH;
                break;

            case DatePeriod::YEAR:
                $format = self::FORMAT_YEAR;
                break;

            default:
                throw new Exception('wrong format');
        }

        return $format;
    }
}
