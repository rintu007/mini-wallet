<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveOldTransactionsJob;
use Illuminate\Console\Command;

class ArchiveOldTransactions extends Command
{
    protected $signature = 'wallet:archive-transactions';
    protected $description = 'Archive transactions older than 24 months';

    public function handle()
    {
        $this->info('Starting transaction archiving...');
        
        ArchiveOldTransactionsJob::dispatch();
        
        $this->info('Transaction archiving job dispatched successfully.');
        return Command::SUCCESS;
    }
}