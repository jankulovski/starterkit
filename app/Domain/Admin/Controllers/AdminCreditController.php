<?php

namespace App\Domain\Admin\Controllers;

use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminCreditController extends Controller
{
    /**
     * Adjust credits for a user (add or deduct).
     * Requires admin privileges.
     */
    public function adjustCredits(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $user = User::findOrFail($request->user_id);
        $amount = $request->amount;
        $reason = $request->reason;

        // Determine if this is an addition or deduction
        if ($amount > 0) {
            // Add credits
            $user->addCredits(
                abs($amount),
                'admin_grant',
                "Admin grant: {$reason}",
                [
                    'adjusted_by' => $request->user()->id,
                    'adjusted_by_email' => $request->user()->email,
                    'adjusted_at' => now()->toIso8601String(),
                ]
            );

            Log::info('Admin granted credits', [
                'admin_id' => $request->user()->id,
                'admin_email' => $request->user()->email,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'amount' => $amount,
                'reason' => $reason,
                'new_balance' => $user->fresh()->credits_balance,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully added {$amount} credits to {$user->name}",
                'new_balance' => $user->fresh()->credits_balance,
            ]);
        } else {
            // Deduct credits
            $deductAmount = abs($amount);

            // Check if user has enough credits
            if (!$user->hasCredits($deductAmount)) {
                return response()->json([
                    'success' => false,
                    'message' => "User only has {$user->credits_balance} credits. Cannot deduct {$deductAmount}.",
                ], 400);
            }

            $user->chargeCredits(
                $deductAmount,
                'admin_deduction',
                [
                    'reason' => $reason,
                    'adjusted_by' => $request->user()->id,
                    'adjusted_by_email' => $request->user()->email,
                    'adjusted_at' => now()->toIso8601String(),
                ]
            );

            Log::warning('Admin deducted credits', [
                'admin_id' => $request->user()->id,
                'admin_email' => $request->user()->email,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'amount' => $deductAmount,
                'reason' => $reason,
                'new_balance' => $user->fresh()->credits_balance,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully deducted {$deductAmount} credits from {$user->name}",
                'new_balance' => $user->fresh()->credits_balance,
            ]);
        }
    }

    /**
     * Get credit transaction history for a user.
     */
    public function getCreditHistory(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = User::findOrFail($request->user_id);
        $limit = $request->input('limit', 50);

        $transactions = \App\Domain\Billing\Models\CreditTransaction::where('user_id', $user->id)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'description' => $transaction->description,
                    'balance_after' => $transaction->balance_after,
                    'metadata' => $transaction->metadata,
                    'created_at' => $transaction->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'current_balance' => $user->credits_balance,
            ],
            'transactions' => $transactions,
        ]);
    }
}
