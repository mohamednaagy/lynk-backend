<?php

declare(strict_types=1);

namespace App\Support\Money\Formatters;

use Money\Money;
use Money\MoneyFormatter;

use function assert;
use function str_pad;
use function strlen;
use function substr;

/**
 * Formats a Money object as a decimal string.
 */
final class DecimalMoneyFormatter implements MoneyFormatter
{
    public function __construct(private int $subunit)
    {
    }

    /** @psalm-return numeric-string */
    public function format(Money $money): string
    {
        $valueBase = $money->getAmount();
        $negative = $valueBase[0] === '-';

        if ($negative) {
            $valueBase = substr($valueBase, 1);
        }

        $valueLength = strlen($valueBase);

        if ($valueLength > $this->subunit) {
            $formatted = substr($valueBase, 0, $valueLength - $this->subunit);
            $decimalDigits = substr($valueBase, $valueLength - $this->subunit);

            if (strlen($decimalDigits) > 0) {
                $formatted .= '.'.$decimalDigits;
            }
        } else {
            $formatted = '0.'.str_pad('', $this->subunit - $valueLength, '0').$valueBase;
        }

        if ($negative) {
            $formatted = '-'.$formatted;
        }

        assert($formatted !== '');

        /** @psalm-var numeric-string $formatted */
        return $formatted;
    }
}
