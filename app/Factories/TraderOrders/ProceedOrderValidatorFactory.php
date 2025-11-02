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
    public static function handle(string $proceedCase): TraderOrderProceedCase
    {
        return match ($proceedCase) {
            FinancingOrderProceedCase::ClientWakalaAccepted => new ClientWakalaAcceptedCase,
            FinancingOrderProceedCase::getDescription(FinancingOrderProceedCase::ContractSigned) => new ContractSignedCase,
            FinancingOrderProceedCase::getDescription(FinancingOrderProceedCase::ContractSignedDelivery) => new ContractSignedDeliveryCase,
            FinancingOrderProceedCase::getDescription(FinancingOrderProceedCase::ContractAndClientWakalaCompleted) => new ContractAndClientWakalaCompletedCase,
            FinancingOrderProceedCase::getDescription(FinancingOrderProceedCase::IgnoreAndSell) => new IgnoreAndSellCase,
            FinancingOrderProceedCase::getDescription(FinancingOrderProceedCase::ConfirmDeliver) => new DeliveryConfirmationCase,
            default => throw new InvalidArgumentException("Unknown proceed case: {$proceedCase}"),
        };
    }
}
