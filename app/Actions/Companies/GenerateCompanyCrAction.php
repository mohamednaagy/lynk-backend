<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GenerateCompanyCr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateCompanyCrAction implements GenerateCompanyCr
{
    /**
     * @return string
     */
    public function handle(): string
    {
        return $this->generateRandomUniqueStringForCompanyCr();
    }

    private function generateRandomUniqueStringForCompanyCr()
    {
        $string = Str::random(10);
        if (DB::table('companies')->where('company_cr', $string)->exists()) {
            return $this->generateRandomUniqueStringForCompanyCr();
        }

        return $string;
    }
}
