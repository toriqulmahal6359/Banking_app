<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get all transactions for the user
        $transactions = Transaction::where('user_id', $user->id)->get();

        // Calculate the current balance
        $currentBalance = $user->balance - $transactions->sum('amount');

        return response()->json(['transactions' => $transactions, 'current_balance' => $currentBalance]);
    }

    public function deposit(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get all deposit transactions for the user
        $depositTransactions = Transaction::where('user_id', $user->id)->where('transaction_type', 'deposit')->get();

        return response()->json(['deposit_transactions' => $depositTransactions]);
    }

    public function storeDeposit(Request $request)
    {

        // Validate input
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        // Get the authenticated user
        $user = Auth::user();

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 400);
        }

        try {
            // Attempt to authenticate the user using JWT token
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token absent'], 401);
        }

        // Create a new deposit transaction
        $transaction = new Transaction([
            'user_id' => $user->id,
            'transaction_type' => 'deposit',
            'amount' => $request->input('amount'),
            'fee' => 0, // No fee for deposits
            'date' => now(),
        ]);
        $transaction->save();

        // Update the user's balance
        $user->balance += $request->input('amount');
        $user->save();

        return response()->json(['message' => 'Deposit successful', 'transaction' => $transaction]);
    }

    public function withdrawal(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get all withdrawal transactions for the user
        $withdrawalTransactions = Transaction::where('user_id', $user->id)->where('transaction_type', 'withdrawal')->get();

        return response()->json(['withdrawal_transactions' => $withdrawalTransactions]);
    }

    public function storeWithdrawal(Request $request)
    {
        // Validate input
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Calculate withdrawal fee based on account type
        $accountType = $user->account_type;
        $withdrawalFee = ($accountType === 'Individual') ? $request->input('amount') * 0.00015 : $request->input('amount') * 0.00025;

        // Check for free withdrawal conditions for Individual accounts
        $isFriday = Carbon::now()->dayOfWeek === Carbon::FRIDAY;
        $monthlyWithdrawalAmount = Transaction::where('user_id', $user->id)
            ->where('transaction_type', 'withdrawal')
            ->whereMonth('date', Carbon::now()->month)
            ->sum('amount');
        $isFirst5KWithdrawal = ($monthlyWithdrawalAmount + $request->input('amount')) <= 5000;

        // Apply free withdrawal conditions
        if ($isFriday || $isFirst5KWithdrawal) {
            $withdrawalFee = 0;
        }

        // Calculate the total amount to be deducted (including the fee)
        $totalAmountToDeduct = $request->input('amount') + $withdrawalFee;

        // Check if the user has enough balance for the withdrawal
        if ($user->balance >= $totalAmountToDeduct) {
            // Create a new withdrawal transaction
            $transaction = new Transaction([
                'user_id' => $user->id,
                'transaction_type' => 'withdrawal',
                'amount' => -$totalAmountToDeduct, // Withdrawals are negative values
                'fee' => $withdrawalFee,
                'date' => now(),
            ]);
            $transaction->save();

            // Update the user's balance
            $user->balance -= $totalAmountToDeduct;
            $user->save();

            return response()->json(['message' => 'Withdrawal successful', 'transaction' => $transaction]);
        } else {
            return response()->json(['message' => 'Insufficient balance for withdrawal'], 400);
        }
    }
}
