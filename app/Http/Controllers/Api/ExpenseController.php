<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    /**
     * I-return ang tanang records. 
     * Ang search ug filtering buhaton na sa Frontend.
     */
    public function index()
    {
        return response()->json([
            // Active records
            'expenses' => Expense::orderBy('date_incurred', 'desc')->get(),
            // Deleted records (Archived)
            'trashed' => Expense::onlyTrashed()->orderBy('deleted_at', 'desc')->get(),
            // Get distinct categories and projects even if they are in trashed records
            'categories' => Expense::withTrashed()->distinct()->pluck('category'),
            'projects' => Expense::withTrashed()->distinct()->pluck('project'),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item' => 'required|string|max:255',
            'category' => 'required|string',
            'project' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'date_incurred' => 'required|date',
            'status' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $expense = Expense::create($request->all());

        return response()->json([
            'message' => 'Expense logged successfully',
            'data' => $expense
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'item' => 'required|string',
            'category' => 'required|string',
            'project' => 'required|string',
            'amount' => 'required|numeric',
            'date_incurred' => 'required|date',
            'status' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $expense->update($request->all());

        return response()->json([
            'message' => 'Expense updated successfully',
            'data' => $expense
        ]);
    }

    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'Record deleted successfully']);
    }

    public function restore($id)
    {
        // Pangitaon ang record sa mga na-delete
        $expense = Expense::onlyTrashed()->findOrFail($id);
        $expense->restore(); // I-restore ang record

        return response()->json([
            'message' => 'Record restored successfully',
            'data' => $expense
        ]);
    }
}