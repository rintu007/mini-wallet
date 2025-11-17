<?php

namespace App\Console\Commands;

use App\Jobs\BalanceReconciliationJob;
use Illuminate\Console\Command;

class RunBalanceReconciliation extends Command
{
    protected $signature = 'wallet:reconcile-balances';
    protected $description = 'Run balance reconciliation for all users';

    public function handle()
    {
        $this->info('Starting balance reconciliation...');
        
        BalanceReconciliationJob::dispatch();
        
        $this->info('Balance reconciliation job dispatched successfully.');
        return Command::SUCCESS;
    }
}