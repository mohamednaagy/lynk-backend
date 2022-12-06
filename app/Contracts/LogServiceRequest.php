<?php

namespace App\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * This interface will used by any event to when a job/driver comminucates with third party.
 * The request/response from the third party will be fired using the event that implements
 * this interface to be utilized in the application.
 */
interface LogServiceRequest
{
    public function name(): string;

    public function extraProperties(): array;

    public function description(): string;

    public function subject(): Model|null;

    public function causer(): Model|null;

    public function timestamp(): Carbon;
}
