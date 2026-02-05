<?php

namespace App\Services\Health\Contracts;

use App\Services\Health\CheckResult;

interface HealthCheck
{
    public function name(): string;

    public function run(): CheckResult;
}
