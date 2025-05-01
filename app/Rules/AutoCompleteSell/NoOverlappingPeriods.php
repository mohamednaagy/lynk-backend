<?php

namespace App\Rules\AutoCompleteSell;

use App\Models\ClientAutoSellPeriod;
use Exception;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class NoOverlappingPeriods implements Rule
{
    protected ?int $clientId;

    protected array $existingPeriodIds;

    public function __construct(?int $clientId = null, array $existingPeriodIds = [])
    {
        $this->clientId = $clientId;
        $this->existingPeriodIds = $existingPeriodIds;
    }

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

        // Check for overlapping periods within the input array
        for ($i = 0; $i < count($normalized) - 1; $i++) {
            $current = $normalized[$i];
            $next = $normalized[$i + 1];

            if ($next['start']->lessThanOrEqualTo($current['end'])) {
                $this->errorMessage = __('validation.periods_overlapped');

                return false;
            }
        }

        // If this is an update operation, check for overlapping with existing periods
        if ($this->clientId) {
            $existingPeriods = ClientAutoSellPeriod::where('company_lender_client_id', $this->clientId)
                ->whereNotIn('id', $this->existingPeriodIds)
                ->get();

            foreach ($normalized as $newPeriod) {
                foreach ($existingPeriods as $existingPeriod) {
                    $existingStart = Carbon::parse($existingPeriod->effective_start)->startOfDay();
                    $existingEnd = Carbon::parse($existingPeriod->effective_end)->endOfDay();

                    if (
                        ($newPeriod['start']->between($existingStart, $existingEnd)) ||
                        ($newPeriod['end']->between($existingStart, $existingEnd)) ||
                        ($existingStart->between($newPeriod['start'], $newPeriod['end'])) ||
                        ($existingEnd->between($newPeriod['start'], $newPeriod['end']))
                    ) {
                        $this->errorMessage = __('validation.periods_overlapped_with_existing');

                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function message(): string
    {
        return $this->errorMessage ?: 'The auto-sell periods are invalid.';
    }
}
