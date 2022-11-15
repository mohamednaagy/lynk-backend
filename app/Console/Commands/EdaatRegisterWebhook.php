<?php

namespace App\Console\Commands;

use App\Support\Edaat\EdaatService;
use Illuminate\Console\Command;

class EdaatRegisterWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'edaat:register-webhook {--payment=} {--bill=} {--reconcile=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register Edaat Webhook';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(EdaatService $edaatService)
    {
        if ($edaatService->registerWebhook(
            $this->getUrl($this->option('payment')),
            $this->getUrl($this->option('bill')),
            $this->getUrl($this->option('reconcile')),
        )) {
            $this->line('webhook registered successfully');

            return Command::SUCCESS;
        }
        $this->error('unable to register webhook');

        return Command::FAILURE;
    }

    private function getUrl($route)
    {
        if (! $route || filter_var($route, FILTER_VALIDATE_URL)) {
            return $route;
        }

        return route($route);
    }
}
