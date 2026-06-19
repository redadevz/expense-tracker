<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Expense;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Requests\StoreExpenseRequest;


class ExpenseController extends Controller
{
    public function index(Request $request){

        $month = $request->query('month');
        
        return Inertia::render('Expenses/Index', [
            'expenses' => Expense::orderBy('date', 'desc')->get(),
            'total' => Expense::sum('amount'),
            'month' => $month ? $month : null,
            'monthTotal' => $month ? Expense::query()
                ->whereYear('date', now()->year)
                ->whereMonth('date', $month)
                ->sum('amount') : null,

        ]);
    }


    public function store(StoreExpenseRequest $request){

        Expense::create($request->validated());

        return back()->with('success', 'Expense added successfully');
    }


    public function update(UpdateExpenseRequest $request, Expense $expense){
        $expense->update($request->validated());

        return back()->with('success', 'Expense updated successfully'); 
    }


    public function destroy(Expense $expense){
        $expense->delete();

        return back()->with('success', 'Expense deleted successfully');
    }


    public function export() : StreamedResponse
    {
        $fileName = 'expenses-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Date', 'Description', 'Amount']);

            Expense::query()->orderBy('date')->chunk(500, function ($expenses) use ($output) {
                foreach ($expenses as $expense) {
                    fputcsv($output, [
                        $expense->id,
                        $expense->date->format('Y-m-d'),
                        $expense->description,
                        $expense->amount,
                    ]);
                }
            });

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

}
