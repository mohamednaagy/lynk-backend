<?php

namespace App\Console\Commands;

use App\Jobs\OverwriteOldTransferOwnershipToLenderDocumentToCorrectDates;
use Illuminate\Console\Command;

class OverwriteOldTransferOwnershipToLenderDocumentToCorrectDatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ownership-document:regenerate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 're-generate the ownership document after optimizing the dates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        dispatch(new OverwriteOldTransferOwnershipToLenderDocumentToCorrectDates);

        return Command::SUCCESS;
    }
}
