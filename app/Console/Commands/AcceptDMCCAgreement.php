<?php

namespace App\Console\Commands;

use App\Support\DMCC\DMCCService;
use Illuminate\Console\Command;

class AcceptDMCCAgreement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dmcc:accept-agreement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Accpet DMCC Agreement';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(DMCCService $DMCCService)
    {
        if ($DMCCService->acceptAgreemt()) {
            $this->line('Agreement Successfully Accepted');

            return Command::SUCCESS;
        }

        $this->error('Unable to Accept the Agreement');

        return Command::FAILURE;
    }
}
