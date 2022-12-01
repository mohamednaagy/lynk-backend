<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrdersVolume;
use App\Enums\DatePeriod;
use App\Models\FinancingOrder;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GetOrdersVolumeAction implements GetOrdersVolume
{
    public const FORMAT_WEEKS = 'weeks';

    public const FORMAT_MONTH = 'Y-m';

    public const FORMAT_YEAR = 'Y';

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
            ->selectRaw('COUNT(*) as orders_total')
            ->when(
                $startingDate,
                function ($query) use ($startingDate) {
                    $query->whereDate('created_at', '>=', $startingDate);
                }
            )->when(
                $endingDate,
                function ($query) use ($endingDate) {
                    $query->whereDate('created_at', '<=', $endingDate);
                }
            )
            ->when(
                $period == DatePeriod::YEAR,
                function ($query) {
                    $query->selectRaw("DATE_FORMAT(created_at, '%Y') as label");
                }
            )
            ->when(
                $period == DatePeriod::MONTH,
                function ($query) {
                    $query->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as label");
                }
            )
            ->when(
                $period == DatePeriod::WEEK,
                function ($query) {
                    $query->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%U') as label");
                }
            )
            ->groupBy('label')
            ->pluck('orders_total', 'label');

        if ($orders->isEmpty()) {
            return [];
        }

        // get all periods even if not contain values
        $periodBetween = $this->getPeriodBetween(
            $orders->keys()->first(),
            $orders->keys()->last(),
            $this->getFormatByPeriod($period)
        );

        // fill empty periods with 0
        return array_replace(array_fill_keys($periodBetween, 0), $this->formatOrdersByPeriod($period, $orders->toArray()));
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
        return match ($format) {
            self::FORMAT_WEEKS => $this->weekFormat($fromYear, $toYear),
            self::FORMAT_MONTH => $this->monthFormat($fromYear, $toYear),
            self::FORMAT_YEAR => $this->yearFormat($fromYear, $toYear),
        };
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

    /**
     * determine orders format
     *
     * @param  mixed  $period
     * @param  array  $orders
     * @return array
     */
    protected function formatOrdersByPeriod(string $period, array $orders)
    {
        return match ($period) {
            DatePeriod::WEEK => $this->formatOrdersInWeeks($orders),
            default => $orders
        };
    }

    /**
     * format orders in weeks
     *
     * @param  array  $orders
     * @return array
     */
    protected function formatOrdersInWeeks(array $orders)
    {
        $ordersInWeekFormat = [];
        foreach ($orders as $date => $count) {
            $dateArray = explode('-', $date);
            $year = $dateArray[0];
            $month = $dateArray[1];
            $week = $dateArray[2];
            // fill array by date with new format as key with count as value
            $ordersInWeekFormat[$year.'-'.$month.' '.'(week '.$week.')'] = $count;
        }

        return $ordersInWeekFormat;
    }

    /**
     * get weeks between two dates
     *
     * @param  mixed  $from
     * @param  mixed  $to
     * @return array<string>
     */
    protected function weekFormat($from, $to)
    {
        $arrayOfDateFrom = explode('-', $from);
        $arrayOfDateTo = explode('-', $to);

        $yearMonthFrom = $arrayOfDateFrom[0].'-'.$arrayOfDateFrom[1];
        $yearMonthTo = $arrayOfDateTo[0].'-'.$arrayOfDateTo[1];
        $range = CarbonPeriod::create(date($yearMonthFrom), date($yearMonthTo));

        $yearInWeeks = [];
        foreach ($range as $month) {
            $formattedMonth = $month->format('Y-m (W)');
            $month = Str::of($formattedMonth)->replace('(', '(week ');
            if (! in_array($month, $yearInWeeks)) {
                $yearInWeeks[] = $month;
            }
        }

        return $yearInWeeks;
    }

    /**
     * get weeks months two dates
     *
     * @param  mixed  $from
     * @param  mixed  $to
     * @return array<string>
     */
    protected function monthFormat($from, $to)
    {
        $range = CarbonPeriod::create(date($from), date($to));
        $periods = [];
        foreach ($range as $month) {
            $periods[] = $month->format(self::FORMAT_MONTH);
        }

        return $periods;
    }

    /**
     * get years between two dates
     *
     * @param  mixed  $from
     * @param  mixed  $to
     * @return array<string>
     */
    protected function yearFormat($from, $to)
    {
        $periods = [];

        for ($i = $from; $i <= $to; $i++) {
            array_push($periods, $i);
        }

        return $periods;
    }
}
