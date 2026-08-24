<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FixedAsset;
use App\Models\FixedAssetAssignment;
use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubcategory;
use Illuminate\Support\Facades\DB;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $query = FixedAsset::with(['client', 'paymentMethod']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('serial_number', 'like', '%' . $request->search . '%');
            });
        }

        $assetCategory = ExpenseCategory::where('name', 'Activo fijo')->first();
        $assetSubcategories = $assetCategory ? $assetCategory->subcategories : collect();
        
        $baseAssets = $query->latest()->get();
        $allMovements = \App\Models\InventoryMovement::where('item_type', 'fixed_asset')->get();

        $groupedAssets = $assetSubcategories->map(function($sub) use ($baseAssets, $allMovements) {
            $catLower = strtolower(trim($sub->name));
            $assets = $baseAssets->filter(function($a) use ($catLower) {
                return strtolower(trim($a->category)) === $catLower;
            })->values();

            $movIncomes = $allMovements->filter(function($m) use ($catLower) {
                return strtolower(trim($m->item_name)) === $catLower && in_array($m->movement_type, ['income', 'return', 'initial_balance']);
            })->sum('quantity');

            $movOutcomes = $allMovements->filter(function($m) use ($catLower) {
                return strtolower(trim($m->item_name)) === $catLower && $m->movement_type === 'outcome';
            })->sum('quantity');

            $individualCount = $assets->count();
            $individualAvailable = $assets->where('status', 'available')->count();
            $individualAssigned = $assets->where('status', 'assigned')->count();

            // Total disponible considerando tanto equipos individuales como stock de movimientos de inventario
            $totalCount = $individualCount + floatval($movIncomes) - floatval($movOutcomes);
            $availableCount = $individualAvailable + floatval($movIncomes) - floatval($movOutcomes);

            return (object)[
                'subcategory' => $sub,
                'assets' => $assets, 
                'count' => max(0, $totalCount),
                'available_count' => max(0, $availableCount),
                'assigned_count' => $individualAssigned,
            ];
        });
        
        $otherAssets = $baseAssets->whereNotIn('category', $assetSubcategories->pluck('name')->toArray())->values();
        if ($otherAssets->count() > 0) {
             $groupedAssets->push((object)[
                'subcategory' => (object)['name' => 'Otros', 'id' => 'otros'],
                'assets' => $otherAssets,
                'count' => $otherAssets->count(),
                'available_count' => $otherAssets->where('status', 'available')->count(),
                'assigned_count' => $otherAssets->where('status', 'assigned')->count(),
            ]);
        }
        
        $clients = Client::all();
        $paymentMethods = PaymentMethod::all();
        $assetCategory = ExpenseCategory::where('name', 'Activo fijo')->first();

        return view('fixed_assets.index', compact('groupedAssets', 'clients', 'paymentMethods', 'assetSubcategories', 'assetCategory'));
    }

    public function category($id, Request $request)
    {
        $subcategory = ExpenseSubcategory::findOrFail($id);
        
        $query = FixedAsset::with(['client', 'paymentMethod'])
                           ->where('category', $subcategory->name);
                           
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('serial_number', 'like', '%' . $request->search . '%');
            });
        }
        
        $assets = $query->latest()->get();
        $clients = Client::all();
        $paymentMethods = PaymentMethod::all();
        
        $assetCategory = ExpenseCategory::where('name', 'Activo fijo')->first();
        $assetSubcategories = $assetCategory ? $assetCategory->subcategories : collect();
        
        return view('fixed_assets.category', compact('subcategory', 'assets', 'clients', 'paymentMethods', 'assetSubcategories', 'assetCategory'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'expense_subcategory_id' => 'required|exists:expense_subcategories,id',
            'purchase_cost' => 'nullable|numeric',
        ]);

        try {
            DB::transaction(function() use ($request) {
                $subcategory = ExpenseSubcategory::findOrFail($request->expense_subcategory_id);
                
                $asset = FixedAsset::create([
                    'name' => $request->name,
                    'category' => $subcategory->name,
                    'internal_code' => $request->internal_code,
                    'serial_number' => $request->serial_number,
                    'status' => 'available',
                    'purchase_date' => $request->purchase_date,
                    'purchase_cost' => $request->purchase_cost,
                    'payment_method_id' => $request->payment_method_id,
                    'voucher_number' => $request->voucher_number,
                    'notes' => $request->notes,
                ]);

                if ($request->purchase_cost > 0 && $request->payment_method_id) {
                    Expense::create([
                        'description' => 'Compra de Activo Fijo: ' . $request->name,
                        'amount' => $request->purchase_cost,
                        'payment_method_id' => $request->payment_method_id,
                        'date' => now()->format('Y-m-d H:i:s'),
                        'real_date' => $request->purchase_date,
                        'receipt_number' => $request->voucher_number,
                        'expense_category_id' => $subcategory->expense_category_id,
                        'expense_subcategory_id' => $subcategory->id,
                        'user_id' => auth()->id() ?? 1 // fallback if auth is null in certain CLI cases
                    ]);
                }
            });
            
            return redirect()->back()->with('success', 'Activo fijo registrado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al registrar el activo fijo: ' . $e->getMessage());
        }
    }

    public function assign(Request $request, FixedAsset $fixedAsset)
    {
        $isInternal = $request->assignment_destination === 'internal';

        $request->validate([
            'client_id' => $isInternal ? 'nullable' : 'required|exists:clients,id',
            'assigned_date' => $isInternal ? 'nullable' : 'required|date',
            'assignment_type' => $isInternal ? 'nullable' : 'required|in:prestado,alquilado',
            'amount' => 'nullable|numeric|min:0',
            'notes' => $isInternal ? 'required|string' : 'nullable|string'
        ]);

        if ($fixedAsset->status !== 'available') {
            return back()->with('error', 'El equipo no está disponible para asignación.');
        }

        $assignment = FixedAssetAssignment::create([
            'fixed_asset_id' => $fixedAsset->id,
            'client_id' => $isInternal ? null : $request->client_id,
            'assignment_type' => $isInternal ? 'interno' : $request->assignment_type,
            'amount' => $isInternal ? null : $request->amount,
            'assigned_date' => $isInternal ? now()->format('Y-m-d') : $request->assigned_date,
            'payment_frequency' => $isInternal ? null : $request->payment_frequency,
            'rental_mode' => $isInternal ? 'indefinite' : $request->rental_mode,
            'total_installments' => (!$isInternal && $request->rental_mode === 'fixed') ? $request->total_installments : null,
            'notes' => $request->notes,
        ]);

        if ($request->assignment_type === 'alquilado' && $request->amount > 0) {
            $date = \Carbon\Carbon::parse($request->assigned_date);
            $count = $request->rental_mode === 'fixed' ? $request->total_installments : 12; // if indefinite, pre-generate 12

            for ($i = 1; $i <= $count; $i++) {
                if ($request->payment_frequency === 'diario') {
                    $date->addDay();
                } elseif ($request->payment_frequency === 'semanal') {
                    $date->addWeek();
                } elseif ($request->payment_frequency === 'quincenal') {
                    $date->addDays(15);
                } elseif ($request->payment_frequency === 'mensual') {
                    $date->addMonth();
                }

                \App\Models\FixedAssetInstallment::create([
                    'fixed_asset_assignment_id' => $assignment->id,
                    'installment_number' => $i,
                    'due_date' => $date->format('Y-m-d'),
                    'amount' => $request->amount,
                    'status' => 'pending'
                ]);
            }
        }

        $fixedAsset->update([
            'status' => 'assigned',
            'current_client_id' => $isInternal ? null : $request->client_id,
        ]);

        return redirect()->back()->with('success', 'Equipo asignado correctamente.');
    }

    public function returnAsset(Request $request, FixedAsset $fixedAsset)
    {
        $request->validate([
            'returned_date' => 'required|date',
        ]);

        if ($fixedAsset->status !== 'assigned') {
            return back()->with('error', 'El equipo no está asignado.');
        }

        $assignment = FixedAssetAssignment::where('fixed_asset_id', $fixedAsset->id)
            ->whereNull('returned_date')
            ->latest()
            ->first();

        if ($assignment) {
            $assignment->update([
                'returned_date' => $request->returned_date,
            ]);
        }

        $fixedAsset->update([
            'status' => 'available',
            'current_client_id' => null,
        ]);

        return redirect()->back()->with('success', 'Equipo devuelto correctamente.');
    }

    public function registerIncome(Request $request, \App\Models\FixedAssetInstallment $installment)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id'
        ]);

        if ($installment->status === 'paid') {
            return back()->with('error', 'Esta cuota ya fue cobrada.');
        }

        $cashbox = \App\Models\Cashbox::whereNull('closed_at')->latest('id')->first();
        if (!$cashbox) {
            return back()->with('error', 'Debe abrir una caja antes de registrar un ingreso.');
        }

        $movement = \App\Models\CashboxMovement::create([
            'cashbox_id' => $cashbox->id,
            'user_id' => auth()->id() ?? 1,
            'payment_method_id' => $request->payment_method_id,
            'type' => 'income',
            'amount' => $installment->amount,
            'date' => now()->format('Y-m-d H:i:s'),
            'note' => 'Cobro de Alquiler: ' . $installment->assignment->fixedAsset->name . ' (Cuota ' . $installment->installment_number . ')'
        ]);

        $installment->update([
            'status' => 'paid',
            'paid_date' => now()->format('Y-m-d'),
            'cashbox_movement_id' => $movement->id
        ]);
        
        // If indefinite rental and we are getting close to the end of pre-generated schedule, generate more
        if ($installment->assignment->rental_mode === 'indefinite') {
            $latestInstallment = $installment->assignment->installments()->latest('installment_number')->first();
            if ($latestInstallment->installment_number - $installment->installment_number < 3) {
                // Generate 6 more installments
                $date = \Carbon\Carbon::parse($latestInstallment->due_date);
                for ($i = 1; $i <= 6; $i++) {
                    if ($installment->assignment->payment_frequency === 'diario') {
                        $date->addDay();
                    } elseif ($installment->assignment->payment_frequency === 'semanal') {
                        $date->addWeek();
                    } elseif ($installment->assignment->payment_frequency === 'quincenal') {
                        $date->addDays(15);
                    } elseif ($installment->assignment->payment_frequency === 'mensual') {
                        $date->addMonth();
                    }

                    \App\Models\FixedAssetInstallment::create([
                        'fixed_asset_assignment_id' => $installment->assignment->id,
                        'installment_number' => $latestInstallment->installment_number + $i,
                        'due_date' => $date->format('Y-m-d'),
                        'amount' => $installment->amount,
                        'status' => 'pending'
                    ]);
                }
            }
        }

        return back()->with('success', 'Cuota cobrada exitosamente.');
    }

    public function updateStatus(Request $request, FixedAsset $fixedAsset)
    {
        $request->validate([
            'status' => 'required|in:available,assigned,maintenance,retired',
        ]);

        $fixedAsset->update([
            'status' => $request->status,
        ]);

        if ($request->status == 'retired' || $request->status == 'maintenance') {
             $fixedAsset->update(['current_client_id' => null]);
             
             // Update any active assignment
             $assignment = FixedAssetAssignment::where('fixed_asset_id', $fixedAsset->id)
                ->whereNull('returned_date')
                ->latest()
                ->first();

            if ($assignment) {
                $assignment->update([
                    'returned_date' => now(),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Estado del equipo actualizado.');
    }

    public function registerExpense(Request $request, FixedAsset $fixedAsset)
    {
        $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'real_date' => 'required|date'
        ]);

        $assetCategory = \App\Models\ExpenseCategory::where('name', 'Activo fijo')->first();
        if (!$assetCategory) {
            return back()->with('error', 'No se encontró la categoría de Activo fijo.');
        }

        $subcategory = \App\Models\ExpenseSubcategory::where('expense_category_id', $assetCategory->id)
            ->where('name', $fixedAsset->category)
            ->first();

        \App\Models\Expense::create([
            'description' => 'Gasto (' . $fixedAsset->name . '): ' . $request->description,
            'amount' => $request->amount,
            'payment_method_id' => $request->payment_method_id,
            'date' => now()->format('Y-m-d H:i:s'),
            'real_date' => $request->real_date,
            'receipt_number' => $request->receipt_number,
            'expense_category_id' => $assetCategory->id,
            'expense_subcategory_id' => $subcategory ? $subcategory->id : null,
            'user_id' => auth()->id() ?? 1,
        ]);

        return back()->with('success', 'Gasto para el activo registrado exitosamente.');
    }
}
