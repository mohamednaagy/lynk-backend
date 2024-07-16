<?php

use App\Enums\MurabhaStep;
use App\Enums\Trader;

return [
    Trader::Lynk => MurabhaStep::ContractSigned,
    Trader::Bursam => MurabhaStep::ContractSigned,
    Trader::FakeDmcc => MurabhaStep::ClientWakala,
    Trader::Dmcc => MurabhaStep::ClientWakala,
];
