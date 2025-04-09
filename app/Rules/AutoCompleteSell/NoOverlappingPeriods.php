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
        // Convert the input value to a boolean
        $autoCompleteSell = filter_var(request()->input('auto_complete_sell'), FILTER_VALIDATE_BOOLEAN);

        // Skip the check if the auto_complete_sell is false
        if (! $autoCompleteSell) {
            return true;
        }

        if (! is_array($value)) {
            $this->errorMessage = 'The periods field must be an array.';

            return false;
        }

        $normalized = [];

        foreach ($value as $index => $period) {
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
