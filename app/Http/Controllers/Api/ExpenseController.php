<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use Illuminate\Support\Carbon;

class ExpenseController extends Controller
{
    /**
     * List all expenses with search, period filters, and pagination.
     */
    public function index(Request $request)
    {
        $query = Expense::query()->orderBy('expense_date', 'desc');

        // Text search (Provider name, tax ID, NCF)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('provider_name', 'like', "%{$search}%")
                  ->orWhere('provider_tax_id', 'like', "%{$search}%")
                  ->orWhere('ncf', 'like', "%{$search}%");
            });
        }

        // Period filter (Year and Month)
        $year = $request->query('year');
        $month = $request->query('month');
        if ($year && $month) {
            $month = str_pad($month, 2, '0', STR_PAD_LEFT);
            $startDate = "{$year}-{$month}-01";
            $endDate = Carbon::parse($startDate)->endOfMonth()->toDateString();
            $query->whereBetween('expense_date', [$startDate, $endDate]);
        }

        $expenses = $query->paginate(25);

        return response()->json($expenses);
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'provider_name' => 'required|string|max:200',
            'provider_tax_id' => 'nullable|string|max:20',
            'ncf' => 'nullable|string|max:13',
            'expense_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'expense_type' => 'required|string|size:2',
            'payment_method' => 'required|string|size:2',
            'notes' => 'nullable|string',
        ]);

        if ($request->user()) {
            $data['created_by'] = $request->user()->id;
        }

        $expense = Expense::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Gasto registrado exitosamente.',
            'data' => $expense
        ], 201);
    }

    /**
     * Show a single expense details.
     */
    public function show($id)
    {
        $expense = Expense::findOrFail($id);
        return response()->json($expense);
    }

    /**
     * Update the specified expense.
     */
    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $data = $request->validate([
            'provider_name' => 'required|string|max:200',
            'provider_tax_id' => 'nullable|string|max:20',
            'ncf' => 'nullable|string|max:13',
            'expense_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'expense_type' => 'required|string|size:2',
            'payment_method' => 'required|string|size:2',
            'notes' => 'nullable|string',
        ]);

        $expense->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Gasto actualizado exitosamente.',
            'data' => $expense
        ]);
    }

    /**
     * Delete the specified expense.
     */
    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gasto eliminado exitosamente.'
        ]);
    }
}
