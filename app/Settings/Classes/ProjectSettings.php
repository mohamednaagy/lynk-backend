<?php

namespace App\Settings\Classes;

use App\Settings\Casters\ProjectCaster;
use App\Support\ProjectSettings\Project;
use Spatie\LaravelSettings\Settings;

class ProjectSettings extends Settings
{
    public Project $project;

    public static function casts(): array
    {
        return [
            'project' => ProjectCaster::class,
        ];
    }

    public static function group(): string
    {
        return 'project';
    }
}
