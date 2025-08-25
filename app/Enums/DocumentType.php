<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class DocumentType extends Enum implements LocalizedEnum
{
    // Wakala and Contract Documents
    const CLIENT_WAKALA = 'client_wakala';

    const TRANSFER_OWNERSHIP_TO_LENDER = 'transfer_ownership_to_lender';

    // Selling and Confirmation Documents
    const SELLING_COMMODITY_TO_CUSTOMER = 'selling_commodity_to_customer';

    const SELL_CONFIRMATION_DOCUMENT = 'sell_confirmation_document';

    const SELLING_PLEDGE_CERTIFICATE = 'selling_pledge_certificate';

    // Bursam Certificates
    const BURSAM_BID_CERTIFICATE = 'bursam_bid_certificate';

    const BURSAM_STB_CERTIFICATE = 'bursam_stb_certificate';

    const BURSAM_OTC_CERTIFICATE = 'bursam_otc_certificate';

    // Transaction Documents
    const VOUCHER_RECEIPT = 'voucher_receipt';

    const ZATCA_INVOICE = 'zatca_invoice';

    /**
     * Get localized name for the enum value
     */
    public static function getLocalizationKey(): string
    {
        return 'enums.document_type';
    }

    /**
     * Get all document types that require trader order context
     */
    public static function getTraderOrderRequiredTypes(): array
    {
        return [
            self::CLIENT_WAKALA,
            self::TRANSFER_OWNERSHIP_TO_LENDER,
            self::SELLING_COMMODITY_TO_CUSTOMER,
            self::SELL_CONFIRMATION_DOCUMENT,
            self::SELLING_PLEDGE_CERTIFICATE,
            self::BURSAM_BID_CERTIFICATE,
            self::BURSAM_STB_CERTIFICATE,
            self::BURSAM_OTC_CERTIFICATE,
        ];
    }

    /**
     * Get all document types that require transaction context
     */
    public static function getTransactionRequiredTypes(): array
    {
        return [
            self::VOUCHER_RECEIPT,
            self::ZATCA_INVOICE,
        ];
    }

    /**
     * Check if document type requires trader order context
     */
    public function requiresTraderOrder(): bool
    {
        return in_array($this->value, self::getTraderOrderRequiredTypes());
    }

    /**
     * Check if document type requires transaction context
     */
    public function requiresTransaction(): bool
    {
        return in_array($this->value, self::getTransactionRequiredTypes());
    }
}
