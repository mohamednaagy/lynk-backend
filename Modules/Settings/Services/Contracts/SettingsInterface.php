<?php

namespace Modules\Settings\Services\Contracts;

use Spatie\LaravelSettings\Settings;

interface SettingsInterface
{
    /**
     * @return array
     */
    public function rules(): array;

    /**
     * @param array $data
     * @return void
     */
    public function update(array $data): void;
}
