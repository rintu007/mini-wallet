<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BalanceReconciliationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;

    public function handle()
    {
        Log::info('Starting balance reconciliation job');

        $totalUsers = User::count();
        $processed = 0;
        $discrepancies = 0;
        $corrected = 0;

        User::chunk(1000, function ($users) use (&$processed, &$discrepancies, &$corrected, &$totalUsers) {
            foreach ($users as $user) {
                $this->reconcileUserBalance($user, $discrepancies, $corrected);
                $processed++;
            }

            Log::info("Balance reconciliation progress: {$processed}/{$totalUsers} users processed");
        });

        Log::info("Balance reconciliation completed", [
            'total_users' => $totalUsers,
            'processed' => $processed,
            'discrepancies_found' => $discrepancies,
            'discrepancies_corrected' => $corrected
        ]);
    }

    private function reconcileUserBalance(User $user, &$discrepancies, &$corrected)
    {
        try {
            // Calculate balance from transactions
            $calculatedBalance = $this->calculateBalanceFromTransactions($user);

            // Compare with stored balance
            $storedBalance = (float) $user->balance;
            $difference = abs($calculatedBalance - $storedBalance);

            // If discrepancy found and it's significant (> 0.01)
            if ($difference > 0.01) {
                $discrepancies++;

                Log::warning('Balance discrepancy found', [
                    'user_id' => $user->id,
                    'stored_balance' => $storedBalance,
                    'calculated_balance' => $calculatedBalance,
                    'difference' => $difference
                ]);

                // Auto-correct if discrepancy is reasonable (< $1000)
                if ($difference < 1000) {
                    DB::transaction(function () use ($user, $calculatedBalance, &$corrected) {
                        $user->balance = $calculatedBalance;
                        $user->save();

                        // Log the correction
                        Log::info('Balance auto-corrected', [
                            'user_id' => $user->id,
                            'old_balance' => $user->getOriginal('balance'),
                            'new_balance' => $calculatedBalance
                        ]);

                        $corrected++;
                    });
                }
            }

        } catch (\Exception $e) {
            Log::error('Error reconciling user balance', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function calculateBalanceFromTransactions(User $user): float
    {
        // Get all transactions for the user (both sent and received)
        $sentAmount = Transaction::where('sender_id', $user->id)
            ->sum('total_amount');

        $receivedAmount = Transaction::where('receiver_id', $user->id)
            ->sum('amount');

        // Initial balance (you might want to store this separately)
        $initialBalance = 0; // This should come from user registration or initial deposit

        return $initialBalance + $receivedAmount - $sentAmount;
    }

    public function failed(\Exception $exception)
    {
        Log::error('Balance reconciliation job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}