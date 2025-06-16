<?php

namespace App\Rules;

use App\Enums\CommodityTypeStatus;
use App\Models\CommodityType;
use Illuminate\Contracts\Validation\Rule;

class CheckCommodityTypeActiveRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    private $provider;

    public function __construct($provider)
    {
        $this->provider = $provider;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($value === null) {
            return true;
        }

        return CommodityType::where('id', $value)
            ->where('provider', $this->provider)
            ->where('status', CommodityTypeStatus::Active)
            ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
