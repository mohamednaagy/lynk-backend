<?php

namespace App\Rules\AutoCompleteSell;

use Exception;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class NoOverlappingPeriods implements Rule
{
    public string $errorMessage = '';

    public function passes($attribute, $value): bool
    {
        $normalized = [];

        foreach ($value as $index => $period) {
            if (isset($period['is_deleted'])) {
                continue;
            }

            try {
                $start = Carbon::parse($period['effective_start'])->startOfDay();
                $end = Carbon::parse($period['effective_end'])->endOfDay();

                $normalized[] = ['start' => $start, 'end' => $end, 'index' => $index];
            } catch (Exception $e) {
                $this->errorMessage = __('validation.invalid_date_format');

                return false;
            }
        }

        usort($normalized, fn ($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);

        for ($i = 0; $i < count($normalized) - 1; $i++) {
            $current = $normalized[$i];
            $next = $normalized[$i + 1];

            if ($next['start']->lessThanOrEqualTo($current['end'])) {
                $this->errorMessage = __('validation.periods_overlapped');

                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return $this->errorMessage ?: 'The auto-sell periods are invalid.';
    }
}
