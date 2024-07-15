<?php

use App\Enums\MurabhaStep;
use App\Enums\Trader;

return [
    Trader::Lynk => [MurabhaStep::ContractSigned],
    Trader::Bursam => [MurabhaStep::ClientWakala, MurabhaStep::ContractSigned],
    Trader::FakeDmcc => [MurabhaStep::ClientWakala, MurabhaStep::ContractSigned],
    Trader::Dmcc => [MurabhaStep::ClientWakala, MurabhaStep::ContractSigned],
];
