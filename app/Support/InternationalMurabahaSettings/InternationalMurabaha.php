<?php

namespace App\Support\InternationalMurabahaSettings;

class InternationalMurabaha
{
    /**
     * Constructor for InternationalMurabaha settings
     *
     * @param  int  $bursam_default_preferred_commodity_type  The default preferred commodity type for Bursam
     */
    public function __construct(
        private int $bursam_default_preferred_commodity_type,
    ) {}

    /**
     * Create an InternationalMurabaha instance from an array of data
     *
     * @param  array  $data  Input data array containing settings
     * @return InternationalMurabaha A new InternationalMurabaha instance
     */
    public static function fromArray(array $data): InternationalMurabaha
    {
        return new static(
            $data['bursam_default_preferred_commodity_type'],
        );
    }

    /**
     * Get the default preferred commodity type for Bursam
     *
     * @return int The default preferred commodity type
     */
    public function getBursamDefaultPreferredCommodityType(): int
    {
        return $this->bursam_default_preferred_commodity_type;
    }
}
