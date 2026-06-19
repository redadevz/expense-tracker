<?php

use App\Http\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;

// Main page: expense list + summaries
Route::get('/', [ExpenseController::class, 'index'])->name('expenses.index');

// CSV export — declared before the {expense} routes so "export" isn't treated as an id
Route::get('/expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');

// Create / update / delete
Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
