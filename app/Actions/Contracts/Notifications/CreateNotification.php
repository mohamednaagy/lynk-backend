<?php

namespace App\Actions\Contracts\Notifications;

interface CreateNotification
{
    /**
     * @param  array  $data
     */
    public function handle(array $data);
}
