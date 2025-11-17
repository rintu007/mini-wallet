<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MonitorBalanceDiscrepanciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Check for large discrepancies that need manual intervention
        $largeDiscrepancies = $this->findLargeBalanceDiscrepancies();

        if ($largeDiscrepancies->isNotEmpty()) {
            $this->sendDiscrepancyAlert($largeDiscrepancies);
        }

        Log::info('Balance discrepancy monitoring completed', [
            'large_discrepancies_found' => $largeDiscrepancies->count()
        ]);
    }

    private function findLargeBalanceDiscrepancies()
    {
        return User::whereRaw('ABS(balance - (
            SELECT COALESCE(SUM(
                CASE 
                    WHEN receiver_id = users.id THEN amount 
                    WHEN sender_id = users.id THEN -total_amount 
                    ELSE 0 
                END
            ), 0) 
            FROM transactions 
            WHERE sender_id = users.id OR receiver_id = users.id
        )) > 1000') // Discrepancies larger than $1000
        ->get();
    }

    private function sendDiscrepancyAlert($usersWithDiscrepancies)
    {
        // In a real application, you might send email, Slack notification, etc.
        Log::critical('LARGE BALANCE DISCREPANCIES DETECTED', [
            'affected_users' => $usersWithDiscrepancies->pluck('id'),
            'discrepancy_count' => $usersWithDiscrepancies->count()
        ]);

        // Example email alert (you'd need to configure mail)
        // Mail::to('admin@example.com')->send(new BalanceDiscrepancyAlert($usersWithDiscrepancies));
    }
}