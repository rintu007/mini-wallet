<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        $transactions = Transaction::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'transactions' => $transactions,
            'current_balance' => $user->balance,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01|max:1000000',
        ]);

        try {
            $sender = $request->user();
            $receiver = \App\Models\User::findOrFail($request->receiver_id);

            $transaction = $this->transactionService->transferMoney(
                $sender,
                $receiver,
                $request->amount
            );

            // Return the complete transaction data including the new balance
            return response()->json([
                'message' => 'Transfer completed successfully',
                'transaction' => $transaction->load(['sender', 'receiver']),
                'new_balance' => $sender->fresh()->balance,
            ], 201);

        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'amount' => [$e->getMessage()],
            ]);
        }
    }
}