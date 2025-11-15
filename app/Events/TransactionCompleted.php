<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction->load(['sender', 'receiver']);
    }

    public function broadcastOn()
    {
        return [
            new Channel('user.' . $this->transaction->sender_id),
            new Channel('user.' . $this->transaction->receiver_id),
        ];
    }

    public function broadcastAs()
    {
        return 'transaction.completed';
    }

    public function broadcastWith()
    {
        return [
            'transaction' => $this->transaction,
            'type' => $this->transaction->sender_id === auth()->id() ? 'sent' : 'received'
        ];
    }
}