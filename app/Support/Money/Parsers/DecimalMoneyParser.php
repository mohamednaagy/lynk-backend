<?php

declare(strict_types=1);

namespace App\Support\Money\Parsers;

use Money\Currency;
use Money\Exception\ParserException;
use Money\Money;
use Money\MoneyParser;
use Money\Number;

use function ltrim;
use function preg_match;
use function sprintf;
use function str_pad;
use function strlen;
use function substr;
use function trim;

/**
 * Parses a decimal string into a Money object.
 */
final class DecimalMoneyParser implements MoneyParser
{
    public const DECIMAL_PATTERN = '/^(?P<sign>-)?(?P<digits>\d+)?\.?(?P<fraction>\d+)?$/';

    public function __construct(private int $subunit)
    {
    }

    public function parse(string $money, Currency $fallbackCurrency = null): Money
    {
        if ($fallbackCurrency === null) {
            throw new ParserException('DecimalMoneyParser cannot parse currency symbols. Use fallbackCurrency argument');
        }

        $decimal = trim($money);

        if ($decimal === '') {
            return new Money(0, $fallbackCurrency);
        }

        if (! preg_match(self::DECIMAL_PATTERN, $decimal, $matches) || ! isset($matches['digits'])) {
            throw new ParserException(sprintf('Cannot parse "%s" to Money.', $decimal));
        }

        $negative = isset($matches['sign']) && $matches['sign'] === '-';

        $decimal = $matches['digits'];

        if ($negative) {
            $decimal = '-'.$decimal;
        }

        if (isset($matches['fraction'])) {
            $fractionDigits = strlen($matches['fraction']);
            $decimal .= $matches['fraction'];
            $decimal = Number::roundMoneyValue($decimal, $this->subunit, $fractionDigits);

            if ($fractionDigits > $this->subunit) {
                $decimal = substr($decimal, 0, $this->subunit - $fractionDigits);
            } elseif ($fractionDigits < $this->subunit) {
                $decimal .= str_pad('', $this->subunit - $fractionDigits, '0');
            }
        } else {
            $decimal .= str_pad('', $this->subunit, '0');
        }

        if ($negative) {
            $decimal = '-'.ltrim(substr($decimal, 1), '0');
        } else {
            $decimal = ltrim($decimal, '0');
        }

        if ($decimal === '' || $decimal === '-') {
            $decimal = '0';
        }

        /** @psalm-var numeric-string $decimal */
        return new Money($decimal, $fallbackCurrency);
    }
}
