<?php

namespace App\Console\Commands;

use App\Support\DMCC\DMCCService;
use Illuminate\Console\Command;

class ProcessDMCCNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dmcc:notification-process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process DMCC Notification';

    /**
     * Execute the console command.
     *
     * @param  DMCCService  $DMCCService
     * @return void
     */
    public function handle(DMCCService $DMCCService): void
    {
        $DMCCService->notificationHandler();
    }
}
