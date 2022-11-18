<?php

namespace App\Enums;

class ErrorCode
{
    public const GENERAL_CODE = 0000;

    public const OTPIFY_WRONG_USER = 1001;

    public const OTPIFY_ALREADY_USED = 1002;

    public const OTPIFY_ADDITIONAL_CHECK = 1003;

    public const OTPIFY_EXPIRED = 1004;

    public const OTPIFY_INVALID = 1005;

    public const OTPIFY_DRIVERS_CONFIGURATION = 1006;

    public const OTPIFY_CODE_INVALID = 1007;

    public const EMAIL_NOT_VERIFIED = 1008;

    public const FILE_NOT_FOUND = 1009;

    public const UNABLE_TO_CANCEL_ORDER = 1010;

    public const ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE = 1011;

    public const BALANCE_NOT_ENOUGH = 1012;
}
