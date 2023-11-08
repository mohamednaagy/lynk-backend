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

    public const WEBHOOK_LIMIT_TYPE_NOT_FOUND = 1012;

    public const X_COMPANY_INVALID = 1013;

    public const BALANCE_NOT_ENOUGH = 1014;

    public const COMPANY_NOT_ACTIVE = 1015;

    public const ORDER_NOT_UPDATABLE = 1016;

    public const ENQUIRY_CLOSED_ALREADY = 1017;

    public const ORDER_ALREADY_APPROVED = 1018;

    public const WRONG_DATA = 1019;

    public const CLIENT_WAKALA_ACCEPTED = 1020;

    public const ORDER_STILL_PENDING = 1021;

    public const ORDER_IS_REJECTED = 1022;

    public const TRADER_NOT_SUPPORTED = 1023;

    public const ORDER_IS_ALREADY_COMPLETED = 1024;

    public const ORDER_ALREADY_HAS_ACTIVE_TRADER_ORDER = 1025;

    public const COMMODITY_MARKET_IS_UNAVAILABLE = 1026;

    public const ORDER_REQUIRE_CLIENT_VERIFICATION = 1027;

    public const COMPANY_NO_MATCH_ORDER_COST_AND_VALUE = 1028;

    public const ORDER_COST_WITHOUT_VAT_AND_WITH_VAT_INCORRECT = 1029;

    public const ORDER_HAS_COMPLETED_TRADER_ORDER = 10230;
}
