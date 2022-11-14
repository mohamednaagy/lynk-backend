<?php

namespace App\Settings\Classes;

use Spatie\LaravelSettings\Settings;

class ProjectSettings extends Settings
{
    public array $company_name;

    public string $company_cr;

    public string $vat_id;

    public float $vat;

    public array $address_line_one;

    public array $address_line_two;

    public static function group(): string
    {
        return 'project';
    }
}
