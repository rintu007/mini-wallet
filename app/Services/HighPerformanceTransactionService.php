<?php

namespace App\Services;

use App\Events\TransactionCompleted;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class HighPerformanceTransactionService
{
    const COMMISSION_RATE = 0.015;
    const MAX_RETRY_ATTEMPTS = 3;
    const RETRY_DELAY_MS = 100; // milliseconds

    public function transferMoney(User $sender, User $receiver, float $amount): Transaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Transfer amount must be positive.');
        }

        if ($sender->id === $receiver->id) {
            throw new InvalidArgumentException('Cannot transfer money to yourself.');
        }

        $commissionFee = $amount * self::COMMISSION_RATE;
        $totalAmount = $amount + $commissionFee;

        // Use retry mechanism for concurrent requests
        return $this->retryTransaction(function() use ($sender, $receiver, $amount, $commissionFee, $totalAmount) {
            return $this->executeAtomicTransfer($sender, $receiver, $amount, $commissionFee, $totalAmount);
        });
    }

    private function retryTransaction(callable $operation, int $attempt = 1)
    {
        try {
            return $operation();
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if it's a deadlock or lock wait timeout
            if ($this->isRetryableError($e) && $attempt < self::MAX_RETRY_ATTEMPTS) {
                Log::warning("Transaction retry attempt {$attempt}", [
                    'error' => $e->getMessage(),
                    'next_attempt_in_ms' => self::RETRY_DELAY_MS * $attempt
                ]);

                // Exponential backoff
                usleep(self::RETRY_DELAY_MS * $attempt * 1000);
                return $this->retryTransaction($operation, $attempt + 1);
            }
            throw $e;
        }
    }

    private function isRetryableError(\Illuminate\Database\QueryException $e): bool
    {
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();

        // MySQL deadlock (40001) or lock wait timeout (1205)
        return $errorCode == 40001 || 
               $errorCode == 1205 ||
               str_contains($errorMessage, 'Deadlock') ||
               str_contains($errorMessage, 'Lock wait timeout');
    }

    private function executeAtomicTransfer(User $sender, User $receiver, float $amount, float $commissionFee, float $totalAmount): Transaction
    {
        return DB::transaction(function () use ($sender, $receiver, $amount, $commissionFee, $totalAmount) {
            // Use SELECT FOR UPDATE with consistent ordering to prevent deadlocks
            $userIds = [$sender->id, $receiver->id];
            sort($userIds); // Consistent ordering prevents deadlocks

            $lockedUsers = User::whereIn('id', $userIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lockedSender = $lockedUsers[$sender->id];
            $lockedReceiver = $lockedUsers[$receiver->id];

            // Recheck balance after acquiring locks
            if ($lockedSender->balance < $totalAmount) {
                throw new InvalidArgumentException('Insufficient balance.');
            }

            // Update balances atomically
            $lockedSender->balance -= $totalAmount;
            $lockedReceiver->balance += $amount;

            // Save both users in a single operation to minimize lock time
            $lockedSender->save();
            $lockedReceiver->save();

            // Create transaction record
            $transaction = Transaction::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => $amount,
                'commission_fee' => $commissionFee,
                'total_amount' => $totalAmount,
                'description' => "Transfer to {$receiver->name}",
            ]);

            // Load relationships for broadcasting (outside the transaction to reduce lock time)
            DB::afterCommit(function () use ($transaction) {
                $transaction->load(['sender', 'receiver']);
                broadcast(new TransactionCompleted($transaction));
                
                Log::info('Transaction completed and broadcasted', [
                    'transaction_id' => $transaction->id,
                    'sender_id' => $transaction->sender_id,
                    'receiver_id' => $transaction->receiver_id,
                    'amount' => $transaction->amount,
                ]);
            });

            return $transaction;
        }, 3); // Increased transaction attempts
    }

    /**
     * Batch processing for high-volume transfers (useful for bulk operations)
     */
    public function processBatchTransfers(array $transfers): array
    {
        $results = [];
        
        foreach (array_chunk($transfers, 100) as $chunk) { // Process in chunks of 100
            $chunkResults = DB::transaction(function () use ($chunk) {
                $batchResults = [];
                
                foreach ($chunk as $transfer) {
                    try {
                        $transaction = $this->transferMoney(
                            $transfer['sender'],
                            $transfer['receiver'],
                            $transfer['amount']
                        );
                        $batchResults[] = ['success' => true, 'transaction' => $transaction];
                    } catch (\Exception $e) {
                        $batchResults[] = ['success' => false, 'error' => $e->getMessage()];
                    }
                }
                
                return $batchResults;
            });
            
            $results = array_merge($results, $chunkResults);
        }
        
        return $results;
    }
}