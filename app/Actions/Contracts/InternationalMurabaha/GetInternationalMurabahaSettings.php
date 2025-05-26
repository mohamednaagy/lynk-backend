<?php

namespace App\Actions\Contracts\InternationalMurabaha;

use App\Support\InternationalMurabahaSettings\InternationalMurabaha;

interface GetInternationalMurabahaSettings
{
    public function handle(): InternationalMurabaha;
}
