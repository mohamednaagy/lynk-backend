<?php

namespace App\Rules;

use App\Models\Company;
use Exception;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Config;

class WebhookRule implements Rule
{
    protected $company;

    protected $limit;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $type)
    {
        $this->limit = $this->getLimit($type);

        return $this->limit === -1
         ||
         $this->company->webhooks()->where('type', $type)->count() < $this->limit;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.custom_validation.webhook_type_limit', ['limit' => $this->limit]);
    }

    private function getLimit($type)
    {
        $limit = Config::get('webhooks.limits');

        if (! isset($limit[$type])) {
            throw new Exception('Unsupported type');
        }

        return $limit[$type];
    }
}
