<?php

namespace App\Listeners;

use App\Contracts\LogServiceRequest;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogActivity implements ShouldQueue
{
    public function handle(LogServiceRequest $event)
    {
        $activity = activity()
            ->withProperties($event->extraProperties())
            ->event($event->name())
            ->causedBy($event->causer())
            ->createdAt($event->timestamp());

        if ($event->subject()) {
            $activity->performedOn($activity->subject());
        }

        $activity->log($event->description());
    }
}
