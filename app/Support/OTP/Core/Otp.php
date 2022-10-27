<?php

namespace App\Support\OTP\Core;

class Otp
{
    protected $adapter;

    protected array $config;

    public function __construct(OtpAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param  string  $nationalId
     * @return mixed
     */
    public function send(string $nationalId): mixed
    {
        return $this->adapter->send($nationalId);
    }

    /**
     * @param  string  $tcn
     * @param  string  $otp
     * @return mixed
     */
    public function check(string $tcn, string $otp): mixed
    {
        return $this->adapter->check($tcn, $otp);
    }
}
