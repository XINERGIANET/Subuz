<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ExpenseCategory;
use App\Models\ExpenseSubcategory;

class ExpenseCategoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);
        $category = ExpenseCategory::create(['name' => $request->name]);
        return response()->json(['status' => true, 'category' => $category]);
    }

    public function storeSubcategory(Request $request, ExpenseCategory $category)
    {
        $request->validate(['name' => 'required']);
        $subcategory = $category->subcategories()->create(['name' => $request->name]);
        return response()->json(['status' => true, 'subcategory' => $subcategory]);
    }

    public function update(Request $request, ExpenseCategory $expense_category)
    {
        $request->validate(['name' => 'required']);
        $expense_category->update(['name' => $request->name]);
        return response()->json(['status' => true]);
    }

    public function updateSubcategory(Request $request, ExpenseSubcategory $subcategory)
    {
        $request->validate(['name' => 'required']);
        $subcategory->update(['name' => $request->name]);
        return response()->json(['status' => true]);
    }

    public function destroySubcategory(ExpenseSubcategory $subcategory)
    {
        $subcategory->delete();
        return response()->json(['status' => true]);
    }
}
