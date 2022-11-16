<?php

namespace App\Actions\Contracts\ProjectSettings;

use App\Support\ProjectSettings\Project;

interface UpdateProjectSettings
{
    public function handle(array $data): Project;
}
