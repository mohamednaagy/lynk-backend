<?php

if (!function_exists('generateRandomCode')) {
    function generateRandomCode($digits)
    {
        return rand(pow(10, $digits-1), pow(10, $digits)-1);
    }
}
