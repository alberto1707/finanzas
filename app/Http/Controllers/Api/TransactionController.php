<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->transactions()->orderBy('date', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('month') && $request->has('year')) {
            $query->whereYear('date', $request->year)
                  ->whereMonth('date', $request->month);
        }

        return $query->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $transaction = $request->user()->transactions()->create($validated);

        return response()->json($transaction, 201);
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'type' => 'in:income,expense',
            'amount' => 'numeric|min:0.01',
            'description' => 'string|max:255',
            'date' => 'date',
        ]);

        $transaction->update($validated);

        return response()->json($transaction);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted']);
    }

    public function balance(Request $request)
    {
        $user = $request->user();

        $queryIncome = $user->transactions()->where('type', 'income');
        $queryExpense = $user->transactions()->where('type', 'expense');

        if ($request->has('month') && $request->has('year')) {
            $queryIncome->whereYear('date', $request->year)->whereMonth('date', $request->month);
            $queryExpense->whereYear('date', $request->year)->whereMonth('date', $request->month);
        }

        $income = $queryIncome->sum('amount');
        $expense = $queryExpense->sum('amount');

        // Breakdown logic
        $mode = $request->get('mode', 'monthly');
        $query = $user->transactions();

        if ($mode === 'daily') {
            $data = $query->select(
                    DB::raw('date as label'),
                    DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"),
                    DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
                )
                ->where('date', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();
        } else {
            $raw_data = $query->select(
                    DB::raw('YEAR(date) as year'),
                    DB::raw('MONTH(date) as month'),
                    DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"),
                    DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
                )
                ->where('date', '>=', now()->subMonths(6))
                ->groupByRaw('YEAR(date), MONTH(date)')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();

            $data = $raw_data->map(function($item) {
                $item->label = $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                return $item;
            });
        }

        // Extra Stats Logic
        $highestExpense = null;
        $averageDaily = 0;

        if ($request->has('stats')) {
             $statsQuery = $user->transactions()->where('type', 'expense');
             if ($request->has('month') && $request->has('year')) {
                $statsQuery->whereYear('date', $request->year)->whereMonth('date', $request->month);
            }

            $highestExpense = $statsQuery->orderBy('amount', 'desc')->first();

            // Average Daily
            // If specific month selected, divide by days in that month (or days passed so far?)
            // If no filter, maybe average of all time? Let's stick to the filter context.
            $totalExpenseInPeriod = $expense;

            if ($request->has('month') && $request->has('year')) {
                $daysInMonth = \Carbon\Carbon::create($request->year, $request->month)->daysInMonth;
                // If current month, maybe divide by current day? Or just full daysInMonth for projection?
                // Let's use daysInMonth for simplicity of monthly average budget.
                $divisor = $daysInMonth;
            } else {
                 // If no specific month, maybe last 30 days?
                 // Or just use 30 as default divisor if global?
                 // For now, let's assume if no filter, we don't calculate meaningful daily average or use 30.
                 $divisor = 30;
            }

            if ($divisor > 0) {
                $averageDaily = $totalExpenseInPeriod / $divisor;
            }
        }

        return response()->json([
            'total_income' => $income,
            'total_expense' => $expense,
            'balance' => $income - $expense,
            'chart_data' => $data,
            'highest_expense' => $highestExpense ?? null,
            'average_daily_expense' => $averageDaily ?? 0
        ]);
    }
}
