<?php

namespace App\Actions\Contracts\ProjectSettings;

use App\Support\ProjectSettings\Project;

interface GetProjectSettings
{
    public function handle(): Project;
}
