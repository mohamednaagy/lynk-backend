<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\QueryScoper\HasScopes;
use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

class DatabaseNotification extends BaseDatabaseNotification
{
    use HasScopes;
}
