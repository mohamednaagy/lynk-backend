<?php

return [
    'timezone' => env('BURSAM_TIME_ZONE', 'Asia/Riyadh'),
    'market_opening_start_time' => env('BURSAM_MARKET_OPENING_START_TIME', '19:30:00'),
    'market_opening_end_time' => env('BURSAM_MARKET_OPENING_END_TIME', '18:30:00'),
    'friday_break_start_time' => env('BURSAM_FRIDAY_BREAK_START_TIME', '08:15:00'),
    'friday_break_end_time' => env('BURSAM_FRIDAY_BREAK_END_TIME', '08:45:00'),
];
