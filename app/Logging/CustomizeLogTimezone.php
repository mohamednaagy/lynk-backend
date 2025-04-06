<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;

class CustomizeLogTimezone
{
    /**
     * Customize the log timestamp to Saudi Arabia time (Asia/Riyadh).
     *
     * @return void
     */
    public function __invoke($logger)
    {
        $monolog = $logger->getLogger();

        $handler = $monolog->getHandlers()[0];
        $logger->setTimezone(new \DateTimeZone('Asia/Riyadh'));

        $formatter = new LineFormatter(null, null, true, true);
        $formatter->setDateFormat('Y-m-d H:i:s');

        $handler->setFormatter($formatter);
    }
}
