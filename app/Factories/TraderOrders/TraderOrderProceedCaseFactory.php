<?php

namespace App\Factories\TraderOrders;

use App\Contracts\TraderOrders\TraderOrderProceedCase;
use App\Enums\FinancingOrderProceedCase;
use App\Validators\TraderOrders\ProceedOrder\ClientWakalaAcceptedCase;
use App\Validators\TraderOrders\ProceedOrder\ContractAndClientWakalaCompletedCase;
use App\Validators\TraderOrders\ProceedOrder\ContractSignedCase;
use App\Validators\TraderOrders\ProceedOrder\ContractSignedDeliveryCase;
use App\Validators\TraderOrders\ProceedOrder\DeliveryConfirmationCase;
use App\Validators\TraderOrders\ProceedOrder\IgnoreAndSellCase;
use InvalidArgumentException;

class TraderOrderProceedCaseFactory
{
    /**
     * Create a validator for the given proceed case.
     *
     * @param  string  $proceedCase  FinancingOrderProceedCase value or description
     *
     * @throws InvalidArgumentException
     */
    public static function handle(int $proceedCase): TraderOrderProceedCase
    {
        return match ($proceedCase) {
            FinancingOrderProceedCase::ClientWakalaAccepted => new ClientWakalaAcceptedCase,
            FinancingOrderProceedCase::ContractSigned => new ContractSignedCase,
            FinancingOrderProceedCase::ContractSignedDelivery => new ContractSignedDeliveryCase,
            FinancingOrderProceedCase::ContractAndClientWakalaCompleted => new ContractAndClientWakalaCompletedCase,
            FinancingOrderProceedCase::IgnoreAndSell => new IgnoreAndSellCase,
            FinancingOrderProceedCase::ConfirmDeliver => new DeliveryConfirmationCase,
            default => throw new InvalidArgumentException("Unknown proceed case: {$proceedCase}"),
        };
    }
}
