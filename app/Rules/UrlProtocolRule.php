<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

class UrlProtocolRule implements Rule
{
    protected array $defaultProtocols = ['https', 'http'];

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(protected array $protocols = [])
    {
        $this->protocols = $protocols;
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
        if (! is_string($value)) {
            return false;
        }

        $pattern = '~^('.implode('|', $this->getProtocolList()).')://(.*)$~ixu';

        return preg_match($pattern, $value) > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.url_protocol', ['values' => implode(',', $this->getProtocols())]);
    }

    /**
     * get getProtocolList
     *
     * @return array
     */
    private function getProtocolList()
    {
        if ($this->areProtocolsEmpty()) {
            return $this->defaultProtocols;
        }

        $supportedProtocols = Config::get('protocols.supported', []);

        return array_map(function ($protocol) use ($supportedProtocols) {
            $protocol = trim($protocol);
            $protocol = preg_quote($protocol, '/');
            if (! in_array($protocol, $supportedProtocols)) {
                throw new InvalidArgumentException(
                    "Validation rule url_protocols requires a supported protocol. $protocol is not supported."
                );
            }

            return $protocol;
        }, $this->protocols);
    }

    protected function getProtocols()
    {
        return ! $this->areProtocolsEmpty() ?
            $this->protocols
            : $this->defaultProtocols;
    }

    private function areProtocolsEmpty()
    {
        return empty($this->protocols);
    }
}
