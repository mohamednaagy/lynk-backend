<?php

namespace App\Actions\Companies;

use App\Enums\CompanyType;

class CompanyCreationStrategyFactory
{
    public function __construct(
        private NormalCompanyCreationStrategy $normalCompanyCreationStrategy,
        private SpecialPurposeVehicleCompanyCreationStrategy $spvCompanyCreationStrategy,
        private TimeDepositCompanyCreationStrategy $timeDepositCompanyCreationStrategy,
    ) {}

    public function make(int $companyType): CompanyCreationStrategy
    {
        return match ($companyType) {
            CompanyType::Lender => $this->normalCompanyCreationStrategy,
            CompanyType::SpecialPurposeVehicle => $this->spvCompanyCreationStrategy,
            CompanyType::TimeDeposit => $this->timeDepositCompanyCreationStrategy,
            default => $this->normalCompanyCreationStrategy,
        };
    }
}
