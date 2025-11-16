<?php

namespace App\Services;

use App\Events\TransactionCompleted;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class TransactionService
{
    const COMMISSION_RATE = 0.015;

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

        if ($sender->balance < $totalAmount) {
            throw new InvalidArgumentException('Insufficient balance.');
        }

        return DB::transaction(function () use ($sender, $receiver, $amount, $commissionFee, $totalAmount) {
            // Lock sender and receiver rows for update to prevent race conditions
            $lockedSender = User::where('id', $sender->id)->lockForUpdate()->first();
            $lockedReceiver = User::where('id', $receiver->id)->lockForUpdate()->first();

            // Recheck balance after lock
            if ($lockedSender->balance < $totalAmount) {
                throw new InvalidArgumentException('Insufficient balance after lock.');
            }

            // Update balances
            $lockedSender->balance -= $totalAmount;
            $lockedSender->save();

            $lockedReceiver->balance += $amount;
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

            // Reload with relationships for broadcasting
            $transaction->load(['sender', 'receiver']);

            // Broadcast real-time event
            broadcast(new TransactionCompleted($transaction));

            Log::info('Transaction completed', [
                'transaction_id' => $transaction->id,
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => $amount,
            ]);

            return $transaction;
        });
    }
}