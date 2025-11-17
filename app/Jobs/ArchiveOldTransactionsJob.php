<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ArchiveOldTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 7200; // 2 hour timeout for large archives
    public $tries = 3;

    protected $archiveMonths = 24; // Archive transactions older than 24 months

    public function handle()
    {
        Log::info('Starting old transactions archiving job');

        $cutoffDate = Carbon::now()->subMonths($this->archiveMonths);
        $totalArchived = 0;

        // Process in chunks to avoid memory issues
        Transaction::where('created_at', '<', $cutoffDate)
            ->chunkById(1000, function ($transactions) use (&$totalArchived) {
                $this->archiveTransactionsBatch($transactions, $totalArchived);
            });

        Log::info('Old transactions archiving completed', [
            'total_archived' => $totalArchived,
            'cutoff_date' => $cutoffDate->toDateString()
        ]);
    }

    private function archiveTransactionsBatch($transactions, &$totalArchived)
    {
        DB::transaction(function () use ($transactions, &$totalArchived) {
            foreach ($transactions as $transaction) {
                // Insert into archive table
                DB::table('transaction_archives')->insert([
                    'id' => $transaction->id,
                    'sender_id' => $transaction->sender_id,
                    'receiver_id' => $transaction->receiver_id,
                    'amount' => $transaction->amount,
                    'commission_fee' => $transaction->commission_fee,
                    'total_amount' => $transaction->total_amount,
                    'status' => $transaction->status,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                    'archived_at' => now(),
                ]);

                // Delete from main table
                $transaction->delete();

                $totalArchived++;
            }
        });

        Log::info("Archived batch of transactions", [
            'batch_size' => count($transactions),
            'total_archived' => $totalArchived
        ]);
    }

    public function failed(\Exception $exception)
    {
        Log::error('Archive old transactions job failed', [
            'error' => $exception->getMessage()
        ]);
    }
}