<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supply;
use App\Models\FixedAsset;
use App\Models\Product;
use App\Models\Client;
use App\Models\User;
use App\Models\InventoryMovement;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubcategory;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\Cashbox;
use App\Models\CashboxMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Codedge\Fpdf\Fpdf\Fpdf;

class InventoryController extends Controller
{
    public static $clientAssetTypes = ['Exhibidores', 'Congeladores', 'Mostradores', 'Cooler', 'Bidones'];

    public function index(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $isDispatcher = auth()->user()->hasRole('despachador');

        // 0. Consulta de Ventas filtradas por rango de fechas opcional
        $salesQuery = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->where('sales.status', '!=', 'Anulado');

        if ($startDate) {
            $salesQuery->whereDate('sales.date', '>=', $startDate);
        }
        if ($endDate) {
            $salesQuery->whereDate('sales.date', '<=', $endDate);
        }

        $salesTotals = $salesQuery
            ->select('sale_details.product_id', DB::raw('SUM(sale_details.quantity) as total_sold'))
            ->groupBy('sale_details.product_id')
            ->pluck('total_sold', 'product_id');

        // Consumo de Insumos por ventas de productos
        $suppliesSalesTotals = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('product_supplies', 'sale_details.product_id', '=', 'product_supplies.product_id')
            ->where('sales.status', '!=', 'Anulado')
            ->when($startDate, fn($q) => $q->whereDate('sales.date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('sales.date', '<=', $endDate))
            ->select('product_supplies.supply_id', DB::raw('SUM(sale_details.quantity * product_supplies.quantity) as total_consumed'))
            ->groupBy('product_supplies.supply_id')
            ->pluck('total_consumed', 'product_supplies.supply_id');

        // Funciones auxiliares para calcular movimientos según fecha
        $getIncomes = function ($type, $id, $name) use ($startDate, $endDate) {
            $q = InventoryMovement::where('item_type', $type)->whereIn('movement_type', ['income', 'return']);
            if ($id) $q->where('item_id', $id);
            else $q->where('item_name', $name);

            if ($startDate) $q->whereDate('created_at', '>=', $startDate);
            if ($endDate) $q->whereDate('created_at', '<=', $endDate);
            return floatval($q->sum('quantity'));
        };

        $getOutcomesManual = function ($type, $id, $name) use ($startDate, $endDate) {
            $q = InventoryMovement::where('item_type', $type)->where('movement_type', 'outcome');
            if ($id) $q->where('item_id', $id);
            else $q->where('item_name', $name);

            if ($startDate) $q->whereDate('created_at', '>=', $startDate);
            if ($endDate) $q->whereDate('created_at', '<=', $endDate);
            return floatval($q->sum('quantity'));
        };

        $getInitial = function ($type, $id, $name, $currentStock) use ($startDate) {
            $q = InventoryMovement::where('item_type', $type)->where('movement_type', 'initial_balance');
            if ($id) $q->where('item_id', $id);
            else $q->where('item_name', $name);

            $hasInitialRecord = $q->exists();
            $baseInitial = $hasInitialRecord ? floatval($q->sum('quantity')) : floatval($currentStock);

            if (!$startDate) {
                return $baseInitial;
            }

            // Si hay filtro de fecha de inicio, calcular el saldo inicial acumulado previo a esa fecha
            $priorIncomes = floatval(InventoryMovement::where('item_type', $type)
                ->whereIn('movement_type', ['income', 'return'])
                ->when($id, fn($query) => $query->where('item_id', $id), fn($query) => $query->where('item_name', $name))
                ->whereDate('created_at', '<', $startDate)
                ->sum('quantity'));

            $priorOutcomesManual = floatval(InventoryMovement::where('item_type', $type)
                ->where('movement_type', 'outcome')
                ->when($id, fn($query) => $query->where('item_id', $id), fn($query) => $query->where('item_name', $name))
                ->whereDate('created_at', '<', $startDate)
                ->sum('quantity'));

            $priorSales = 0;
            if ($type === 'product' && $id) {
                $priorSales = floatval(DB::table('sale_details')
                    ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                    ->where('sales.status', '!=', 'Anulado')
                    ->where('sale_details.product_id', $id)
                    ->whereDate('sales.date', '<', $startDate)
                    ->sum('sale_details.quantity'));
            } elseif ($type === 'supply' && $id) {
                $priorSales = floatval(DB::table('sale_details')
                    ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                    ->join('product_supplies', 'sale_details.product_id', '=', 'product_supplies.product_id')
                    ->where('sales.status', '!=', 'Anulado')
                    ->where('product_supplies.supply_id', $id)
                    ->whereDate('sales.date', '<', $startDate)
                    ->sum(DB::raw('sale_details.quantity * product_supplies.quantity')));
            }

            return $baseInitial + $priorIncomes - ($priorOutcomesManual + $priorSales);
        };

        // 1. INSUMOS (Bidones, tapas, sellos, etiquetas, bolsas, etc.)
        $suppliesQuery = Supply::query();
        if ($isDispatcher) {
            $suppliesQuery->where('allowed_for_dispatchers', true);
        }
        $supplies = $suppliesQuery->get()->map(function ($supply) use ($suppliesSalesTotals, $getInitial, $getIncomes, $getOutcomesManual) {
            $initial = $getInitial('supply', $supply->id, null, $supply->stock);
            $incomes = $getIncomes('supply', $supply->id, null);

            $salidas_ventas = floatval($suppliesSalesTotals[$supply->id] ?? 0);
            $salidas_manuales = $getOutcomesManual('supply', $supply->id, null);

            $outcomes = $salidas_ventas + $salidas_manuales;
            $saldo_final = $initial + $incomes - $outcomes;

            return (object) [
                'id' => $supply->id,
                'name' => $supply->name,
                'unit' => $supply->unit ?? 'Unidades',
                'allowed_for_dispatchers' => (bool) $supply->allowed_for_dispatchers,
                'saldo_inicial' => $initial,
                'ingresos' => $incomes,
                'salidas' => $outcomes,
                'saldo_final' => $saldo_final,
                'current_stock' => $supply->stock,
            ];
        });

        // 2. ACTIVOS FIJOS (Dispensadores, congeladoras, exhibidores, etc.)
        $assetCategoryObj = ExpenseCategory::where('name', 'like', '%Activo fijo%')->first();
        $subCatObjs = $assetCategoryObj ? $assetCategoryObj->subcategories : collect();
        $subCatNames = $subCatObjs->pluck('name')->toArray();
        $defaultCategories = ['congeladoras', 'exhibidores', 'dispensadores', 'maquina gourmet', 'maquina 1500 kg', 'planta de agua', 'selladora', 'repuestos', 'moto', 'mostradores', 'camion'];
        $dbCategories = FixedAsset::distinct()->pluck('category')->filter()->toArray();
        
        // Unificar categorías preservando nombre formateado
        $allCatNames = array_unique(array_merge($subCatNames, $defaultCategories, $dbCategories));
        $allAssets = FixedAsset::all();

        $assetsData = collect($allCatNames)->map(function ($catName) use ($allAssets, $subCatObjs, $startDate, $endDate, $getInitial, $getIncomes, $getOutcomesManual) {
            $catLower = strtolower(trim($catName));
            $matchingAssets = $allAssets->filter(function ($a) use ($catLower) {
                return strtolower(trim($a->category)) === $catLower;
            });

            $totalCount = $matchingAssets->count();
            $availableCount = $matchingAssets->where('status', 'available')->count();
            $assignedCount = $matchingAssets->where('status', 'assigned')->count();

            $initial = $getInitial('fixed_asset', null, $catName, $totalCount);
            $incomes = $getIncomes('fixed_asset', null, $catName);

            // Asignaciones a clientes en fixed_asset_assignments
            $salidas_asignaciones = floatval(DB::table('fixed_asset_assignments')
                ->join('fixed_assets', 'fixed_asset_assignments.fixed_asset_id', '=', 'fixed_assets.id')
                ->whereRaw('LOWER(TRIM(fixed_assets.category)) = ?', [$catLower])
                ->when($startDate, fn($q) => $q->whereDate('fixed_asset_assignments.assigned_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('fixed_asset_assignments.assigned_date', '<=', $endDate))
                ->count());

            $salidas_manuales = $getOutcomesManual('fixed_asset', null, $catName);
            $outcomes = $salidas_asignaciones + $salidas_manuales;
            $saldo_final = $initial + $incomes - $outcomes;

            // Verificar si está habilitado para despachador
            $matchingSub = $subCatObjs->first(fn($s) => strtolower(trim($s->name)) === $catLower);
            $isAllowed = $matchingSub ? (bool) $matchingSub->allowed_for_dispatchers : $matchingAssets->contains('allowed_for_dispatchers', true);

            return (object) [
                'category' => ucfirst($catName),
                'total_count' => $totalCount,
                'available_count' => $availableCount,
                'assigned_count' => $assignedCount,
                'allowed_for_dispatchers' => $isAllowed,
                'saldo_inicial' => $initial,
                'ingresos' => $incomes,
                'salidas' => $outcomes,
                'saldo_final' => $saldo_final,
            ];
        });

        if ($isDispatcher) {
            $assetsData = $assetsData->filter(fn($a) => $a->allowed_for_dispatchers)->values();
        }

        // 3. PRODUCTOS TERMINADOS (Bolsas de hielo, agua, etc.)
        $productsQuery = Product::where('is_combo', false);
        if ($isDispatcher) {
            $productsQuery->where('allowed_for_dispatchers', true);
        }
        $products = $productsQuery->get()->map(function ($product) use ($salesTotals, $getInitial, $getIncomes, $getOutcomesManual) {
            $initial = $getInitial('product', $product->id, null, $product->stock ?? 0);
            $incomes = $getIncomes('product', $product->id, null);

            $salidas_ventas = floatval($salesTotals[$product->id] ?? 0);
            $salidas_manuales = $getOutcomesManual('product', $product->id, null);

            $outcomes = $salidas_ventas + $salidas_manuales;
            $saldo_final = $initial + $incomes - $outcomes;

            return (object) [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'allowed_for_dispatchers' => (bool) $product->allowed_for_dispatchers,
                'saldo_inicial' => $initial,
                'ingresos' => $incomes,
                'salidas' => $outcomes,
                'saldo_final' => $saldo_final,
                'current_stock' => $product->stock,
            ];
        });

        $clients = Client::orderBy('name', 'asc')->get();
        $dispatchers = User::where('role', 'despachador')->orderBy('name', 'asc')->get();
        $paymentMethods = PaymentMethod::all();

        // 4. CONTROL DE ACTIVOS Y BIDONES POR CLIENTE (Y PLANTA)
        $clientAssets = $this->getClientAssetsMatrix($startDate, $endDate, $request->client_id, $request->asset_type);
        $clientAssetsRows = $clientAssets['rows'];
        $plantaClient = $clientAssets['planta_client'];
        $plantaTotals = $clientAssets['planta_totals'];
        $clientsTotals = $clientAssets['clients_totals'];
        $clientAssetTotals = $clientAssets['totals'];
        $clientGrandTotal = $clientAssets['grand_total'];
        $clientAssetTypes = self::$clientAssetTypes;

        return view('inventories.index', compact(
            'supplies',
            'assetsData',
            'products',
            'clients',
            'dispatchers',
            'paymentMethods',
            'isDispatcher',
            'clientAssetsRows',
            'plantaClient',
            'plantaTotals',
            'clientsTotals',
            'clientAssetTotals',
            'clientGrandTotal',
            'clientAssetTypes'
        ));
    }

    public function toggleDispatcherPermission(Request $request)
    {
        if (auth()->user()->hasRole('despachador')) {
            return response()->json(['status' => false, 'error' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'item_type' => 'required|string|in:supply,product,fixed_asset',
            'item_id' => 'nullable',
            'item_name' => 'nullable|string',
            'allowed' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        if ($request->item_type === 'supply') {
            $item = Supply::find($request->item_id);
            if (!$item) return response()->json(['status' => false, 'error' => 'Insumo no encontrado']);
            $item->allowed_for_dispatchers = $request->allowed;
            $item->save();
        } elseif ($request->item_type === 'product') {
            $item = Product::find($request->item_id);
            if (!$item) return response()->json(['status' => false, 'error' => 'Producto no encontrado']);
            $item->allowed_for_dispatchers = $request->allowed;
            $item->save();
        } elseif ($request->item_type === 'fixed_asset') {
            $catName = $request->item_name ?: $request->item_id;
            $catLower = strtolower(trim($catName));

            // Actualizar o crear subcategoría
            $assetCategory = ExpenseCategory::firstOrCreate(['name' => 'Activo fijo']);
            $subcat = ExpenseSubcategory::firstOrCreate(
                ['expense_category_id' => $assetCategory->id, 'name' => ucfirst($catName)]
            );
            $subcat->allowed_for_dispatchers = $request->allowed;
            $subcat->save();

            // Actualizar activos fijos con esa categoría
            FixedAsset::whereRaw('LOWER(TRIM(category)) = ?', [$catLower])
                ->update(['allowed_for_dispatchers' => $request->allowed]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Permiso actualizado correctamente.',
            'allowed' => (bool) $request->allowed
        ]);
    }

    public function storeInitialBalance(Request $request)
    {
        if (auth()->user()->hasRole('despachador')) {
            return response()->json(['status' => false, 'error' => 'No autorizado para configurar saldos iniciales.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'item_type' => 'required|string|in:supply,fixed_asset,product',
            'item_id' => 'nullable',
            'item_name' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        // Delete existing initial balance movement for this item
        $query = InventoryMovement::where('item_type', $request->item_type)
            ->where('movement_type', 'initial_balance');

        if ($request->item_id) {
            $query->where('item_id', $request->item_id);
        } else {
            $query->where('item_name', $request->item_name);
        }
        $query->delete();

        // Insert new initial balance
        InventoryMovement::create([
            'item_type' => $request->item_type,
            'item_id' => $request->item_id,
            'item_name' => $request->item_name,
            'movement_type' => 'initial_balance',
            'quantity' => $request->quantity,
            'notes' => 'Saldo inicial configurado por usuario',
            'user_id' => auth()->id(),
        ]);

        // Sync stock if supply
        if ($request->item_type === 'supply' && $request->item_id) {
            $supply = Supply::find($request->item_id);
            if ($supply) {
                $incomes = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->whereIn('movement_type', ['income', 'return'])->sum('quantity');
                $outcomes = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->where('movement_type', 'outcome')->sum('quantity');
                $supply->update(['stock' => $request->quantity + $incomes - $outcomes]);
            }
        }

        // Sync stock if product
        if ($request->item_type === 'product' && $request->item_id) {
            $product = Product::find($request->item_id);
            if ($product) {
                $incomes = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->whereIn('movement_type', ['income', 'return'])->sum('quantity');
                $outcomes = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->where('movement_type', 'outcome')->sum('quantity');
                $product->update([
                    'initial_stock' => $request->quantity,
                    'stock' => $request->quantity + $incomes - $outcomes
                ]);
            }
        }

        return response()->json(['status' => true, 'message' => 'Saldo inicial guardado con éxito.']);
    }

    public function storeMovement(Request $request)
    {
        $isDispatcher = auth()->user()->hasRole('despachador');

        $validator = Validator::make($request->all(), [
            'item_type' => 'required|string|in:supply,fixed_asset,product',
            'item_id' => 'nullable',
            'item_name' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'dispatcher_id' => 'nullable|exists:users,id',
            'movement_type' => 'required|string|in:income,outcome,adjustment,return',
            'quantity' => 'required|numeric|gt:0',
            'amount' => 'nullable|numeric|min:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        // Validación de permisos para despachadores
        if ($isDispatcher) {
            if ($request->movement_type !== 'return') {
                return response()->json(['status' => false, 'error' => 'Los despachadores solo pueden registrar devoluciones.'], 403);
            }

            if ($request->item_type === 'supply' && $request->item_id) {
                $supply = Supply::find($request->item_id);
                if (!$supply || !$supply->allowed_for_dispatchers) {
                    return response()->json(['status' => false, 'error' => 'Este insumo no está habilitado para despachadores.'], 403);
                }
            }
            if ($request->item_type === 'product' && $request->item_id) {
                $prod = Product::find($request->item_id);
                if (!$prod || !$prod->allowed_for_dispatchers) {
                    return response()->json(['status' => false, 'error' => 'Este producto no está habilitado para despachadores.'], 403);
                }
            }
        }

        $client = null;
        if ($request->client_id) {
            $client = Client::find($request->client_id);
        }

        // Si es despachador, autoasignar su ID; si es admin/asistente, usar el seleccionado
        $finalDispatcherId = $isDispatcher ? auth()->id() : $request->dispatcher_id;
        $dispatcher = $finalDispatcherId ? User::find($finalDispatcherId) : null;

        $defaultNote = $request->movement_type === 'return' ? 'Devolución de inventario' : 'Movimiento de inventario manual';
        $finalNote = $request->notes ?: $defaultNote;

        InventoryMovement::create([
            'item_type' => $request->item_type,
            'item_id' => $request->item_id,
            'item_name' => $request->item_name,
            'client_id' => $request->client_id,
            'dispatcher_id' => $finalDispatcherId,
            'movement_type' => $request->movement_type,
            'quantity' => $request->quantity,
            'notes' => $finalNote,
            'user_id' => auth()->id(),
        ]);

        $itemName = $request->item_name;

        // Sync stock if supply
        if ($request->item_type === 'supply' && $request->item_id) {
            $supply = Supply::find($request->item_id);
            if ($supply) {
                $itemName = $supply->name;
                $initial = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->where('movement_type', 'initial_balance')->sum('quantity');
                $incomes = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->whereIn('movement_type', ['income', 'return'])->sum('quantity');
                $outcomes = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->where('movement_type', 'outcome')->sum('quantity');
                $supply->update(['stock' => $initial + $incomes - $outcomes]);
            }
        }

        // Sync stock if product
        if ($request->item_type === 'product' && $request->item_id) {
            $product = Product::find($request->item_id);
            if ($product) {
                $itemName = $product->name;
                $initial = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->where('movement_type', 'initial_balance')->sum('quantity');
                $incomes = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->whereIn('movement_type', ['income', 'return'])->sum('quantity');
                $outcomes = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->where('movement_type', 'outcome')->sum('quantity');
                $product->update(['stock' => $initial + $incomes - $outcomes]);
            }
        }

        // Financial & Cashbox Integration if Amount & Payment Method provided
        if ($request->amount > 0 && $request->payment_method_id) {
            $clientNote = $client ? " (Cliente: {$client->name})" : "";
            $dispatcherNote = $dispatcher ? " (Despachador: {$dispatcher->name})" : "";

            if ($request->movement_type === 'income') {
                // Ingreso de inventario comprado = EGRESO / GASTO de dinero
                $category = ExpenseCategory::firstOrCreate(['name' => 'Compra de Inventario']);
                $subcategory = ExpenseSubcategory::firstOrCreate([
                    'expense_category_id' => $category->id,
                    'name' => ucfirst($request->item_type)
                ]);

                Expense::create([
                    'description' => "Compra de inventario: {$itemName} - Cant: {$request->quantity}{$clientNote}{$dispatcherNote}",
                    'amount' => $request->amount,
                    'date' => now()->format('Y-m-d H:i:s'),
                    'real_date' => now()->format('Y-m-d'),
                    'expense_category_id' => $category->id,
                    'expense_subcategory_id' => $subcategory->id,
                    'payment_method_id' => $request->payment_method_id,
                    'user_id' => auth()->id() ?? 1
                ]);
            } elseif ($request->movement_type === 'outcome') {
                // Salida de inventario vendida/cobrada = INGRESO de dinero a caja
                $cashbox = Cashbox::currentOpen();
                if ($cashbox) {
                    CashboxMovement::create([
                        'cashbox_id' => $cashbox->id,
                        'user_id' => auth()->id(),
                        'dispatcher_id' => $finalDispatcherId,
                        'payment_method_id' => $request->payment_method_id,
                        'type' => 'income',
                        'amount' => $request->amount,
                        'note' => "Salida/Venta de inventario: {$itemName} - Cant: {$request->quantity}{$clientNote}{$dispatcherNote}",
                        'date' => now()
                    ]);
                }
            } elseif ($request->movement_type === 'return') {
                // Devolución de inventario (Devolución de cliente con reembolso de dinero) = EGRESO de dinero de caja
                $cashbox = Cashbox::currentOpen();
                if ($cashbox) {
                    CashboxMovement::create([
                        'cashbox_id' => $cashbox->id,
                        'user_id' => auth()->id(),
                        'dispatcher_id' => $finalDispatcherId,
                        'payment_method_id' => $request->payment_method_id,
                        'type' => 'expense',
                        'amount' => $request->amount,
                        'note' => "Devolución de inventario: {$itemName} - Cant: {$request->quantity}{$clientNote}{$dispatcherNote}",
                        'date' => now()
                    ]);
                }
            }
        }

        return response()->json(['status' => true, 'message' => 'Movimiento registrado con éxito.']);
    }

    public function history(Request $request, $itemType, $itemId = null)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $query = InventoryMovement::with(['user', 'client', 'dispatcher'])
            ->where('item_type', $itemType);

        if ($itemId && is_numeric($itemId) && intval($itemId) > 0) {
            $query->where('item_id', $itemId);
        } elseif ($request->item_name) {
            $query->where('item_name', $request->item_name);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $movements = $query->orderBy('created_at', 'desc')->get()->map(function ($m) {
            $typeLabel = 'Movimiento';
            if ($m->movement_type === 'initial_balance') $typeLabel = 'Saldo Inicial';
            if ($m->movement_type === 'income') $typeLabel = 'Ingreso (+)';
            if ($m->movement_type === 'outcome') $typeLabel = 'Salida (-)';
            if ($m->movement_type === 'return') $typeLabel = 'Devolución (+)';
            if ($m->movement_type === 'adjustment') $typeLabel = 'Ajuste';

            $clientName = $m->client ? $m->client->name : null;
            $dispatcherName = $m->dispatcher ? $m->dispatcher->name : null;

            return [
                'id' => $m->id,
                'type_label' => $typeLabel,
                'movement_type' => $m->movement_type,
                'quantity' => floatval($m->quantity),
                'notes' => $m->notes ?: '-',
                'client_name' => $clientName,
                'dispatcher_name' => $dispatcherName,
                'user' => $m->user ? $m->user->name : 'Sistema',
                'date' => $m->created_at ? $m->created_at->format('d/m/Y H:i') : '-',
                'raw_date' => $m->created_at ? $m->created_at->toDateTimeString() : '1970-01-01 00:00:00',
            ];
        });

        // Historial adicional por tipo de ítem
        if ($itemType === 'product' && !empty($itemId) && is_numeric($itemId) && intval($itemId) > 0) {
            $salesQuery = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->leftJoin('clients', 'sales.client_id', '=', 'clients.id')
                ->leftJoin('users as dispatchers', 'sales.dispatcher_id', '=', 'dispatchers.id')
                ->leftJoin('users', 'sales.user_id', '=', 'users.id')
                ->where('sales.status', '!=', 'Anulado')
                ->where('sale_details.product_id', $itemId);

            if ($startDate) {
                $salesQuery->whereDate('sales.date', '>=', $startDate);
            }
            if ($endDate) {
                $salesQuery->whereDate('sales.date', '<=', $endDate);
            }

            $salesMovements = $salesQuery
                ->select(
                    'sales.id as sale_id',
                    'sales.date as date_time',
                    'sale_details.quantity as qty',
                    'sales.order as order',
                    'sales.guide as guide',
                    'clients.name as client_name',
                    'dispatchers.name as dispatcher_name',
                    'users.name as user_name'
                )
                ->get()
                ->map(function ($s) {
                    $clientInfo = $s->client_name ? " (Cliente: {$s->client_name})" : '';
                    $ref = $s->guide ? "Guía: {$s->guide}" : "Pedido: {$s->order}";
                    return [
                        'id' => 'sale_' . $s->sale_id,
                        'type_label' => 'Salida (Venta)',
                        'movement_type' => 'outcome',
                        'quantity' => floatval($s->qty),
                        'notes' => "Venta {$ref}{$clientInfo}",
                        'client_name' => $s->client_name,
                        'dispatcher_name' => $s->dispatcher_name,
                        'user' => $s->user_name ?: 'Sistema',
                        'date' => $s->date_time ? \Carbon\Carbon::parse($s->date_time)->format('d/m/Y H:i') : '-',
                        'raw_date' => $s->date_time ?: '1970-01-01 00:00:00',
                    ];
                });

            $allMovements = $movements->concat($salesMovements)->sortByDesc('raw_date')->values();
            return response()->json($allMovements);
        }

        if ($itemType === 'supply' && !empty($itemId) && is_numeric($itemId) && intval($itemId) > 0) {
            $supplySalesMovements = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->join('product_supplies', 'sale_details.product_id', '=', 'product_supplies.product_id')
                ->join('products', 'sale_details.product_id', '=', 'products.id')
                ->leftJoin('clients', 'sales.client_id', '=', 'clients.id')
                ->leftJoin('users as dispatchers', 'sales.dispatcher_id', '=', 'dispatchers.id')
                ->leftJoin('users', 'sales.user_id', '=', 'users.id')
                ->where('sales.status', '!=', 'Anulado')
                ->where('product_supplies.supply_id', $itemId)
                ->when($startDate, fn($q) => $q->whereDate('sales.date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('sales.date', '<=', $endDate))
                ->select(
                    'sales.id as sale_id',
                    'sales.date as date_time',
                    DB::raw('(sale_details.quantity * product_supplies.quantity) as qty'),
                    'products.name as product_name',
                    'sales.order as order',
                    'sales.guide as guide',
                    'clients.name as client_name',
                    'dispatchers.name as dispatcher_name',
                    'users.name as user_name'
                )
                ->get()
                ->map(function ($s) {
                    $clientInfo = $s->client_name ? " (Cliente: {$s->client_name})" : '';
                    $ref = $s->guide ? "Guía: {$s->guide}" : "Pedido: {$s->order}";
                    return [
                        'id' => 'supply_sale_' . $s->sale_id,
                        'type_label' => 'Salida (Consumo Venta)',
                        'movement_type' => 'outcome',
                        'quantity' => floatval($s->qty),
                        'notes' => "Consumo por venta de {$s->product_name} en {$ref}{$clientInfo}",
                        'client_name' => $s->client_name,
                        'dispatcher_name' => $s->dispatcher_name,
                        'user' => $s->user_name ?: 'Sistema',
                        'date' => $s->date_time ? \Carbon\Carbon::parse($s->date_time)->format('d/m/Y H:i') : '-',
                        'raw_date' => $s->date_time ?: '1970-01-01 00:00:00',
                    ];
                });

            $allMovements = $movements->concat($supplySalesMovements)->sortByDesc('raw_date')->values();
            return response()->json($allMovements);
        }

        if ($itemType === 'fixed_asset' && $request->item_name) {
            $catName = $request->item_name;
            $assignments = DB::table('fixed_asset_assignments')
                ->join('fixed_assets', 'fixed_asset_assignments.fixed_asset_id', '=', 'fixed_assets.id')
                ->leftJoin('clients', 'fixed_asset_assignments.client_id', '=', 'clients.id')
                ->where('fixed_assets.category', 'like', "%{$catName}%")
                ->when($startDate, fn($q) => $q->whereDate('fixed_asset_assignments.assigned_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('fixed_asset_assignments.assigned_date', '<=', $endDate))
                ->select(
                    'fixed_asset_assignments.id as assignment_id',
                    'fixed_asset_assignments.assigned_date',
                    'fixed_asset_assignments.returned_date',
                    'fixed_assets.name as asset_name',
                    'fixed_assets.internal_code',
                    'clients.name as client_name'
                )
                ->get();

            $assetMovements = collect();
            foreach ($assignments as $a) {
                $clientInfo = $a->client_name ? " a cliente {$a->client_name}" : '';
                $codeInfo = $a->internal_code ? " (Cód: {$a->internal_code})" : '';

                $assetMovements->push([
                    'id' => 'assign_' . $a->assignment_id,
                    'type_label' => 'Salida (Asignación)',
                    'movement_type' => 'outcome',
                    'quantity' => 1.00,
                    'notes' => "Asignación de {$a->asset_name}{$codeInfo}{$clientInfo}",
                    'client_name' => $a->client_name,
                    'dispatcher_name' => null,
                    'user' => 'Sistema',
                    'date' => $a->assigned_date ? \Carbon\Carbon::parse($a->assigned_date)->format('d/m/Y H:i') : '-',
                    'raw_date' => $a->assigned_date ?: '1970-01-01 00:00:00',
                ]);

                if ($a->returned_date) {
                    $assetMovements->push([
                        'id' => 'ret_' . $a->assignment_id,
                        'type_label' => 'Ingreso (Devolución)',
                        'movement_type' => 'income',
                        'quantity' => 1.00,
                        'notes' => "Devolución de {$a->asset_name}{$codeInfo}{$clientInfo}",
                        'client_name' => $a->client_name,
                        'dispatcher_name' => null,
                        'user' => 'Sistema',
                        'date' => \Carbon\Carbon::parse($a->returned_date)->format('d/m/Y H:i'),
                        'raw_date' => $a->returned_date,
                    ]);
                }
            }

            $allMovements = $movements->concat($assetMovements)->sortByDesc('raw_date')->values();
            return response()->json($allMovements);
        }

        return response()->json($movements);
    }

    public function storeSupply(Request $request)
    {
        if (auth()->user()->hasRole('despachador')) {
            return response()->json(['status' => false, 'error' => 'No autorizado para crear insumos.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'stock' => 'nullable|numeric|min:0',
            'allowed_for_dispatchers' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        $supply = Supply::create([
            'name' => $request->name,
            'unit' => $request->unit ?: 'Unidades',
            'stock' => $request->stock ?: 0,
            'allowed_for_dispatchers' => $request->has('allowed_for_dispatchers') ? (bool) $request->allowed_for_dispatchers : false,
        ]);

        if ($request->stock > 0) {
            InventoryMovement::create([
                'item_type' => 'supply',
                'item_id' => $supply->id,
                'movement_type' => 'initial_balance',
                'quantity' => $request->stock,
                'notes' => 'Saldo inicial al crear insumo',
                'user_id' => auth()->id(),
            ]);
        }

        return response()->json(['status' => true, 'message' => 'Insumo registrado exitosamente.']);
    }

    /* =========================================================================
     * MÓDULO: CONTROL DE ACTIVOS Y BIDONES POR CLIENTE (Y PLANTA)
     * ========================================================================= */

    public function normalizeAssetType($name)
    {
        $lower = strtolower(trim((string)$name));
        if (str_contains($lower, 'exhibid')) return 'Exhibidores';
        if (str_contains($lower, 'congelad')) return 'Congeladores';
        if (str_contains($lower, 'mostrad')) return 'Mostradores';
        if (str_contains($lower, 'cooler')) return 'Cooler';
        if (str_contains($lower, 'bidon')) return 'Bidones';
        return ucfirst($name ?: 'Otro');
    }

    public function getClientAssetsMatrix($startDate = null, $endDate = null, $clientIdFilter = null, $assetFilter = null)
    {
        // 1. Obtener o asegurar el cliente PLANTA
        $plantaClient = Client::whereRaw("LOWER(TRIM(name)) = 'planta (sede principal)'")
            ->orWhereRaw("LOWER(TRIM(name)) = 'planta'")
            ->orWhereRaw("LOWER(TRIM(name)) = 'planta sub-uz'")
            ->first();

        if (!$plantaClient) {
            $plantaClient = Client::whereRaw('LOWER(TRIM(name)) LIKE ?', ['%planta%'])->orderBy('id', 'desc')->first();
        }

        if (!$plantaClient) {
            $plantaClient = Client::firstOrCreate([
                'name' => 'PLANTA (Sede Principal)',
            ], [
                'document' => '00000000',
                'type' => 'DNI',
                'phone' => '920488526',
                'address' => 'Planta Principal Subuz',
            ]);
        }

        // 2. Traer todos los movimientos de activos en custodia y planta
        $allMovements = InventoryMovement::with(['user', 'dispatcher', 'client'])
            ->where(function ($q) {
                $q->where('item_type', 'client_asset')
                  ->orWhereNotNull('client_id');
            })
            ->get();

        // Determinar todos los clientes con movimientos
        $clientIdsWithMovements = $allMovements->pluck('client_id')->filter()->unique()->toArray();
        if (!in_array($plantaClient->id, $clientIdsWithMovements)) {
            $clientIdsWithMovements[] = $plantaClient->id;
        }

        if ($clientIdFilter) {
            $clientIds = array_values(array_filter([$clientIdFilter]));
        } else {
            $clientIds = $clientIdsWithMovements;
        }

        // Cargar clientes colocando siempre a PLANTA en primer lugar
        $clients = Client::whereIn('id', $clientIds)
            ->get()
            ->sortBy(function ($c) use ($plantaClient) {
                if ($c->id == $plantaClient->id) return '0000000000';
                return strtolower($c->name);
            });

        $rows = collect();

        foreach ($clients as $client) {
            $isPlanta = ($client->id == $plantaClient->id);
            $assetsBreakdown = [];
            $clientTotal = 0;

            foreach (self::$clientAssetTypes as $asset) {

                if ($isPlanta) {
                    // CÁLCULO PARA PLANTA (ALMACÉN CENTRAL):
                    // El stock de Planta refleja el inventario físico disponible en almacén:
                    // (+) Saldo inicial directo de Planta
                    // (+) Ingresos directos a Planta (compras/adquisiciones)
                    // (-) Salidas directas de Planta (bajas/mermas)
                    // (+) Devoluciones recibidas de clientes
                    // (-) Entregas enviadas a clientes

                    $plantaDirectMovs = $allMovements->filter(function ($m) use ($plantaClient, $asset) {
                        return $m->client_id == $plantaClient->id && $this->normalizeAssetType($m->item_name) === $asset;
                    });

                    $otherClientsMovs = $allMovements->filter(function ($m) use ($plantaClient, $asset) {
                        return $m->client_id != $plantaClient->id && $this->normalizeAssetType($m->item_name) === $asset;
                    });

                    // Saldo inicial base directo en almacén Planta
                    $baseInitial = floatval($plantaDirectMovs->where('movement_type', 'initial_balance')->sum('quantity'));

                    // Movimientos previos a la fecha de inicio
                    $priorDirectIncomes = 0;
                    $priorDirectOutcomes = 0;
                    $priorClientReturns = 0;
                    $priorClientDeliveries = 0;

                    if ($startDate) {
                        $priorDirectIncomes = floatval($plantaDirectMovs->filter(function ($m) use ($startDate) {
                            $mDate = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : null);
                            return $mDate && $mDate < $startDate && in_array($m->movement_type, ['income', 'return']);
                        })->sum('quantity'));

                        $priorDirectOutcomes = floatval($plantaDirectMovs->filter(function ($m) use ($startDate) {
                            $mDate = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : null);
                            return $mDate && $mDate < $startDate && in_array($m->movement_type, ['outcome', 'withdrawal']);
                        })->sum('quantity'));

                        $priorClientReturns = floatval($otherClientsMovs->filter(function ($m) use ($startDate) {
                            $mDate = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : null);
                            return $mDate && $mDate < $startDate && in_array($m->movement_type, ['return', 'outcome', 'withdrawal']);
                        })->sum('quantity'));

                        $priorClientDeliveries = floatval($otherClientsMovs->filter(function ($m) use ($startDate) {
                            $mDate = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : null);
                            return $mDate && $mDate < $startDate && in_array($m->movement_type, ['delivery', 'income']);
                        })->sum('quantity'));
                    }

                    $saldoInicial = $baseInitial + ($priorDirectIncomes + $priorClientReturns) - ($priorDirectOutcomes + $priorClientDeliveries);

                    // Movimientos en el rango de fechas
                    $periodDirectMovs = $plantaDirectMovs->filter(function ($m) use ($startDate, $endDate) {
                        if ($m->movement_type === 'initial_balance') return false;
                        $mDate = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : null);
                        if ($startDate && $mDate && $mDate < $startDate) return false;
                        if ($endDate && $mDate && $mDate > $endDate) return false;
                        return true;
                    });

                    $periodOtherMovs = $otherClientsMovs->filter(function ($m) use ($startDate, $endDate) {
                        if ($m->movement_type === 'initial_balance') return false;
                        $mDate = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : null);
                        if ($startDate && $mDate && $mDate < $startDate) return false;
                        if ($endDate && $mDate && $mDate > $endDate) return false;
                        return true;
                    });

                    // Ingresos al almacén de Planta en el periodo: Compras directas + Devoluciones recibidas de clientes
                    $directIncomesPeriod = floatval($periodDirectMovs->filter(fn($m) => in_array($m->movement_type, ['income', 'return']))->sum('quantity'));
                    $clientReturnsPeriod = floatval($periodOtherMovs->filter(fn($m) => in_array($m->movement_type, ['return', 'outcome', 'withdrawal']))->sum('quantity'));
                    $ingresos = $directIncomesPeriod + $clientReturnsPeriod;

                    // Salidas del almacén de Planta en el periodo: Bajas directas + Entregas despachadas a clientes
                    $directOutcomesPeriod = floatval($periodDirectMovs->filter(fn($m) => in_array($m->movement_type, ['outcome', 'withdrawal']))->sum('quantity'));
                    $clientDeliveriesPeriod = floatval($periodOtherMovs->filter(fn($m) => in_array($m->movement_type, ['delivery', 'income']))->sum('quantity'));
                    $salidas = $directOutcomesPeriod + $clientDeliveriesPeriod;

                    $saldoFinal = $saldoInicial + $ingresos - $salidas;

                } else {
                    // CÁLCULO PARA CLIENTE INDIVIDUAL (ACTIVOS PRESTADOS / EN CUSTODIA):
                    // (+) Saldo inicial base del cliente
                    // (+) Entregas recibidas de Planta
                    // (-) Devoluciones enviadas a Planta

                    $clientMovs = $allMovements->filter(function ($m) use ($client, $asset) {
                        return $m->client_id == $client->id && $this->normalizeAssetType($m->item_name) === $asset;
                    });

                    $baseInitial = floatval($clientMovs->where('movement_type', 'initial_balance')->sum('quantity'));

                    $priorIncomes = 0;
                    $priorOutcomes = 0;
                    if ($startDate) {
                        $priorIncomes = floatval($clientMovs->filter(function ($m) use ($startDate) {
                            $mDate = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : null);
                            return $mDate && $mDate < $startDate && in_array($m->movement_type, ['income', 'delivery']);
                        })->sum('quantity'));

                        $priorOutcomes = floatval($clientMovs->filter(function ($m) use ($startDate) {
                            $mDate = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : null);
                            return $mDate && $mDate < $startDate && in_array($m->movement_type, ['outcome', 'return', 'withdrawal']);
                        })->sum('quantity'));
                    }

                    $saldoInicial = $baseInitial + $priorIncomes - $priorOutcomes;

                    $periodMovs = $clientMovs->filter(function ($m) use ($startDate, $endDate) {
                        if ($m->movement_type === 'initial_balance') return false;
                        $mDate = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : null);
                        if ($startDate && $mDate && $mDate < $startDate) return false;
                        if ($endDate && $mDate && $mDate > $endDate) return false;
                        return true;
                    });

                    // Entregas recibidas (+)
                    $ingresos = floatval($periodMovs->filter(fn($m) => in_array($m->movement_type, ['income', 'delivery']))->sum('quantity'));
                    // Devoluciones enviadas (-)
                    $salidas = floatval($periodMovs->filter(fn($m) => in_array($m->movement_type, ['outcome', 'return', 'withdrawal']))->sum('quantity'));

                    $saldoFinal = $saldoInicial + $ingresos - $salidas;
                }

                $assetsBreakdown[$asset] = [
                    'saldo_inicial' => $saldoInicial,
                    'ingresos' => $ingresos,
                    'salidas' => $salidas,
                    'saldo_final' => $saldoFinal,
                ];

                $clientTotal += $saldoFinal;
            }

            // Si se filtró por un activo específico, solo excluir clientes sin actividad si NO se ha filtrado explícitamente por ese cliente
            if ($assetFilter && !$isPlanta && !$clientIdFilter) {
                $hasAssetActivity = ($assetsBreakdown[$assetFilter]['saldo_final'] != 0)
                    || ($assetsBreakdown[$assetFilter]['ingresos'] != 0)
                    || ($assetsBreakdown[$assetFilter]['salidas'] != 0)
                    || ($assetsBreakdown[$assetFilter]['saldo_inicial'] != 0);
                if (!$hasAssetActivity) {
                    continue;
                }
            }

            $displayTotal = $assetFilter ? ($assetsBreakdown[$assetFilter]['saldo_final'] ?? 0) : $clientTotal;

            $rows->push((object) [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'client_document' => $client->document,
                'client_phone' => $client->phone,
                'client_address' => $client->address,
                'is_planta' => $isPlanta,
                'assets' => $assetsBreakdown,
                'total_assets' => $displayTotal,
            ]);
        }

        // Totales separados: Planta vs Clientes vs Gran Total Empresa
        $plantaRow = $rows->first(fn($r) => $r->is_planta);
        $clientsOnlyRows = $rows->filter(fn($r) => !$r->is_planta);

        $plantaTotals = [];
        $clientsTotals = [];
        $grandTotals = [];
        $grandTotalSum = 0;

        foreach (self::$clientAssetTypes as $asset) {
            $pStock = $plantaRow ? ($plantaRow->assets[$asset]['saldo_final'] ?? 0) : 0;
            $cStock = $clientsOnlyRows->sum(fn($r) => $r->assets[$asset]['saldo_final'] ?? 0);
            $totalStock = $pStock + $cStock;

            $plantaTotals[$asset] = $pStock;
            $clientsTotals[$asset] = $cStock;
            $grandTotals[$asset] = $totalStock;
            if (!$assetFilter || $assetFilter === $asset) {
                $grandTotalSum += $totalStock;
            }
        }

        return [
            'planta_client' => $plantaClient,
            'rows' => $rows,
            'planta_row' => $plantaRow,
            'planta_totals' => $plantaTotals,
            'clients_totals' => $clientsTotals,
            'totals' => $grandTotals,
            'grand_total' => $grandTotalSum,
            'asset_types' => self::$clientAssetTypes,
        ];
    }

    public function storeClientAssetMovement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'asset_type' => 'required|string|in:' . implode(',', self::$clientAssetTypes),
            'movement_type' => 'required|string|in:delivery,return,income,outcome',
            'quantity' => 'required|numeric|gt:0',
            'date' => 'required|date',
            'dispatcher_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        $plantaClient = Client::whereRaw("LOWER(TRIM(name)) = 'planta (sede principal)'")
            ->orWhereRaw("LOWER(TRIM(name)) = 'planta'")
            ->orWhereRaw("LOWER(TRIM(name)) = 'planta sub-uz'")
            ->first();

        $isPlanta = ($plantaClient && $request->client_id == $plantaClient->id);

        $movType = $request->movement_type;
        if ($isPlanta) {
            // Para Planta: income (compra/ingreso directo) y outcome (baja/merma directa)
            if ($movType === 'delivery') $movType = 'outcome';
            if ($movType === 'return') $movType = 'income';
            $defaultNote = ($movType === 'income') ? 'Ingreso directo al almacén de Planta' : 'Salida / Baja directa de Planta';
        } else {
            // Para Clientes: delivery (entrega a cliente) y return (devolución del cliente a planta)
            if ($movType === 'income') $movType = 'delivery';
            if ($movType === 'outcome') $movType = 'return';
            $defaultNote = ($movType === 'delivery') ? 'Salida de Planta y entrega al cliente' : 'Devolución del cliente e ingreso a Planta';
        }

        $finalNote = $request->notes ?: $defaultNote;

        $finalDispatcherId = $request->dispatcher_id;
        if (auth()->user()->hasRole('despachador')) {
            $finalDispatcherId = auth()->id();
        }

        $movement = InventoryMovement::create([
            'item_type' => 'client_asset',
            'item_id' => null,
            'item_name' => $request->asset_type,
            'client_id' => $request->client_id,
            'dispatcher_id' => $finalDispatcherId,
            'movement_type' => $movType,
            'quantity' => $request->quantity,
            'date' => $request->date,
            'notes' => $finalNote,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Movimiento de activo registrado con éxito.',
            'movement' => $movement
        ]);
    }

    public function storeClientAssetInitialBalance(Request $request)
    {
        if (auth()->user()->hasRole('despachador')) {
            return response()->json(['status' => false, 'error' => 'No autorizado para configurar saldos iniciales.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'asset_type' => 'required|string|in:' . implode(',', self::$clientAssetTypes),
            'quantity' => 'required|numeric|min:0',
            'date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        // Eliminar saldo inicial previo para este cliente y activo
        InventoryMovement::where('item_type', 'client_asset')
            ->where('client_id', $request->client_id)
            ->where('item_name', $request->asset_type)
            ->where('movement_type', 'initial_balance')
            ->delete();

        InventoryMovement::create([
            'item_type' => 'client_asset',
            'item_id' => null,
            'item_name' => $request->asset_type,
            'client_id' => $request->client_id,
            'movement_type' => 'initial_balance',
            'quantity' => $request->quantity,
            'date' => $request->date ?: now()->toDateString(),
            'notes' => $request->notes ?: 'Saldo inicial configurado por usuario',
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Saldo inicial de ' . $request->asset_type . ' guardado exitosamente.'
        ]);
    }

    public function clientAssetHistory(Request $request, $clientId)
    {
        $client = Client::findOrFail($clientId);
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $assetFilter = $request->asset_type;

        $plantaClient = Client::whereRaw("LOWER(TRIM(name)) = 'planta (sede principal)'")
            ->orWhereRaw("LOWER(TRIM(name)) = 'planta'")
            ->orWhereRaw("LOWER(TRIM(name)) = 'planta sub-uz'")
            ->first();

        $isPlanta = ($plantaClient && $client->id == $plantaClient->id);

        if ($isPlanta) {
            // Historial de PLANTA (Almacén Central):
            // Muestra movimientos directos y todas las entregas/devoluciones con clientes
            $query = InventoryMovement::with(['user', 'dispatcher', 'client'])
                ->where(function ($q) {
                    $q->where('item_type', 'client_asset')
                      ->orWhereNotNull('client_id');
                });

            if ($startDate) {
                $query->where(function ($q) use ($startDate) {
                    $q->whereDate('date', '>=', $startDate)
                      ->orWhere(function ($sq) use ($startDate) {
                          $sq->whereNull('date')->whereDate('created_at', '>=', $startDate);
                      });
                });
            }

            if ($endDate) {
                $query->where(function ($q) use ($endDate) {
                    $q->whereDate('date', '<=', $endDate)
                      ->orWhere(function ($sq) use ($endDate) {
                          $sq->whereNull('date')->whereDate('created_at', '<=', $endDate);
                      });
                });
            }

            $allMovs = $query->get()->filter(function ($m) use ($assetFilter) {
                $norm = $this->normalizeAssetType($m->item_name);
                if ($assetFilter) return $norm === $assetFilter;
                return in_array($norm, self::$clientAssetTypes);
            });

            $movements = $allMovs->map(function ($m) use ($plantaClient) {
                $isDirectPlanta = ($m->client_id == $plantaClient->id);
                $cName = $m->client ? $m->client->name : 'Cliente';
                $mDate = $m->date ? $m->date->format('d/m/Y') : ($m->created_at ? $m->created_at->format('d/m/Y') : '-');

                if ($isDirectPlanta) {
                    if ($m->movement_type === 'initial_balance') {
                        $typeLabel = 'Saldo Inicial Almacén';
                        $movSign = 'initial_balance';
                    } elseif (in_array($m->movement_type, ['income', 'return'])) {
                        $typeLabel = 'Ingreso directo a Almacén (+)';
                        $movSign = 'income';
                    } else {
                        $typeLabel = 'Salida / Baja directa de Almacén (-)';
                        $movSign = 'outcome';
                    }
                    $note = $m->notes ?: '-';
                } else {
                    if (in_array($m->movement_type, ['delivery', 'income'])) {
                        $typeLabel = "Salida por Entrega a: {$cName} (-)";
                        $movSign = 'outcome';
                        $note = "Entrega al cliente {$cName}" . ($m->notes ? " | {$m->notes}" : '');
                    } elseif (in_array($m->movement_type, ['return', 'outcome', 'withdrawal'])) {
                        $typeLabel = "Ingreso por Devolución de: {$cName} (+)";
                        $movSign = 'income';
                        $note = "Devolución del cliente {$cName}" . ($m->notes ? " | {$m->notes}" : '');
                    } else {
                        return null; // Saldos iniciales de clientes no afectan almacén físico de Planta
                    }
                }

                $rawDate = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : '1970-01-01');

                return [
                    'id' => $m->id,
                    'asset_name' => $this->normalizeAssetType($m->item_name),
                    'movement_type' => $movSign,
                    'type_label' => $typeLabel,
                    'quantity' => floatval($m->quantity),
                    'date' => $mDate,
                    'notes' => $note,
                    'dispatcher_name' => $m->dispatcher ? $m->dispatcher->name : '-',
                    'user_name' => $m->user ? $m->user->name : 'Sistema',
                    'can_delete' => (auth()->check() && auth()->user()->hasRole('admin') && $isDirectPlanta),
                    'raw_order' => $rawDate . '_' . str_pad($m->id, 10, '0', STR_PAD_LEFT),
                ];
            })->filter()->sortByDesc('raw_order')->values();

        } else {
            // Historial de CLIENTE INDIVIDUAL
            $query = InventoryMovement::with(['user', 'dispatcher', 'client'])
                ->where('client_id', $clientId);

            if ($startDate) {
                $query->where(function ($q) use ($startDate) {
                    $q->whereDate('date', '>=', $startDate)
                      ->orWhere(function ($sq) use ($startDate) {
                          $sq->whereNull('date')->whereDate('created_at', '>=', $startDate);
                      });
                });
            }

            if ($endDate) {
                $query->where(function ($q) use ($endDate) {
                    $q->whereDate('date', '<=', $endDate)
                      ->orWhere(function ($sq) use ($endDate) {
                          $sq->whereNull('date')->whereDate('created_at', '<=', $endDate);
                      });
                });
            }

            $movements = $query->get()->filter(function ($m) use ($assetFilter) {
                $norm = $this->normalizeAssetType($m->item_name);
                if ($assetFilter) return $norm === $assetFilter;
                return in_array($norm, self::$clientAssetTypes);
            })->sortByDesc(function ($m) {
                $d = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : '1970-01-01');
                return $d . '_' . str_pad($m->id, 10, '0', STR_PAD_LEFT);
            })->values()->map(function ($m) {
                $typeLabel = 'Movimiento';
                if ($m->movement_type === 'initial_balance') $typeLabel = 'Saldo Inicial';
                if (in_array($m->movement_type, ['income', 'delivery'])) $typeLabel = 'Entrega de Planta (+)';
                if (in_array($m->movement_type, ['outcome', 'return', 'withdrawal'])) $typeLabel = 'Devolución a Planta (-)';

                $mDate = $m->date ? $m->date->format('d/m/Y') : ($m->created_at ? $m->created_at->format('d/m/Y') : '-');

                return [
                    'id' => $m->id,
                    'asset_name' => $this->normalizeAssetType($m->item_name),
                    'movement_type' => in_array($m->movement_type, ['income', 'delivery']) ? 'income' : (in_array($m->movement_type, ['outcome', 'return', 'withdrawal']) ? 'outcome' : $m->movement_type),
                    'type_label' => $typeLabel,
                    'quantity' => floatval($m->quantity),
                    'date' => $mDate,
                    'notes' => $m->notes ?: '-',
                    'dispatcher_name' => $m->dispatcher ? $m->dispatcher->name : '-',
                    'user_name' => $m->user ? $m->user->name : 'Sistema',
                    'can_delete' => (auth()->check() && auth()->user()->hasRole('admin')),
                ];
            });
        }

        return response()->json([
            'status' => true,
            'client_name' => $client->name,
            'client_document' => $client->document,
            'is_planta' => $isPlanta,
            'movements' => $movements,
        ]);
    }

    public function destroyClientAssetMovement(Request $request, $movementId)
    {
        if (!auth()->user()->hasRole('admin')) {
            return response()->json(['status' => false, 'error' => 'No autorizado para eliminar movimientos.'], 403);
        }

        $movement = InventoryMovement::find($movementId);
        if (!$movement) {
            return response()->json(['status' => false, 'error' => 'Movimiento no encontrado.'], 404);
        }

        $movement->delete();

        return response()->json(['status' => true, 'message' => 'Movimiento eliminado correctamente.']);
    }

    public function clientAssetsSummaryPdf(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $clientId = $request->client_id;
        $assetFilter = $request->asset_type;

        $matrix = $this->getClientAssetsMatrix($startDate, $endDate, $clientId, $assetFilter);
        $rows = $matrix['rows'];
        $plantaTotals = $matrix['planta_totals'];
        $clientsTotals = $matrix['clients_totals'];
        $totals = $matrix['totals'];
        $grandTotal = $matrix['grand_total'];

        $fpdf = new Fpdf('L', 'mm', 'A4');
        $fpdf->AddPage();
        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');

        // Logo institucional
        if (file_exists(public_path('assets/images/logo.jpg'))) {
            $fpdf->Image(public_path('assets/images/logo.jpg'), 10, 10, 32);
        }

        $fpdf->SetFont('Montserrat', 'B', 15);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(277, 7, utf8_decode('SUBUZ S.A.C.'), 0, 1, 'C');

        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->SetTextColor(40, 40, 40);
        $fpdf->Cell(277, 6, utf8_decode('REPORTE RESUMEN DE ACTIVOS Y BIDONES (PLANTA Y CLIENTES)'), 0, 1, 'C');

        // Periodo
        $period = "Estado general al " . now()->format('d/m/Y');
        if ($startDate && $endDate) {
            $period = "Periodo: " . date('d/m/Y', strtotime($startDate)) . " al " . date('d/m/Y', strtotime($endDate));
        } elseif ($startDate) {
            $period = "Desde: " . date('d/m/Y', strtotime($startDate));
        } elseif ($endDate) {
            $period = "Hasta: " . date('d/m/Y', strtotime($endDate));
        }

        $fpdf->SetFont('Montserrat', '', 9);
        $fpdf->SetTextColor(100, 100, 100);
        $fpdf->Cell(277, 5, utf8_decode($period), 0, 1, 'C');
        $fpdf->Ln(4);

        // Cabecera de la tabla
        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->SetDrawColor(2, 93, 166);
        $fpdf->SetFont('Montserrat', 'B', 8.5);

        $fpdf->Cell(12, 8, utf8_decode('N°'), 1, 0, 'C', true);
        $fpdf->Cell(85, 8, utf8_decode('UBICACIÓN / CLIENTE'), 1, 0, 'L', true);
        $fpdf->Cell(30, 8, utf8_decode('EXHIBIDORES'), 1, 0, 'C', true);
        $fpdf->Cell(30, 8, utf8_decode('CONGELADORES'), 1, 0, 'C', true);
        $fpdf->Cell(30, 8, utf8_decode('MOSTRADORES'), 1, 0, 'C', true);
        $fpdf->Cell(28, 8, utf8_decode('COOLER'), 1, 0, 'C', true);
        $fpdf->Cell(30, 8, utf8_decode('BIDONES'), 1, 0, 'C', true);
        $fpdf->Cell(32, 8, utf8_decode('TOTAL ACTIVOS'), 1, 1, 'C', true);

        // Filas
        $fpdf->SetDrawColor(220, 224, 230);
        $index = 1;

        foreach ($rows as $r) {
            $isPlanta = $r->is_planta;

            if ($isPlanta) {
                $fpdf->SetFillColor(230, 242, 255);
                $fpdf->SetFont('Montserrat', 'B', 8.5);
                $fpdf->SetTextColor(2, 93, 166);
            } else {
                $fpdf->SetFillColor(255, 255, 255);
                $fpdf->SetFont('Montserrat', '', 8);
                $fpdf->SetTextColor(30, 30, 30);
            }

            $clientLabel = $isPlanta ? '[ALMACÉN PLANTA] ' . $r->client_name : $r->client_name;
            if (strlen($clientLabel) > 42) {
                $clientLabel = substr($clientLabel, 0, 39) . '...';
            }

            $fpdf->Cell(12, 7, $isPlanta ? 'P' : $index++, 1, 0, 'C', $isPlanta);
            $fpdf->Cell(85, 7, utf8_decode($clientLabel), 1, 0, 'L', $isPlanta);

            $fpdf->SetTextColor(30, 30, 30);
            $fpdf->Cell(30, 7, number_format($r->assets['Exhibidores']['saldo_final'] ?? 0, 0), 1, 0, 'C', $isPlanta);
            $fpdf->Cell(30, 7, number_format($r->assets['Congeladores']['saldo_final'] ?? 0, 0), 1, 0, 'C', $isPlanta);
            $fpdf->Cell(30, 7, number_format($r->assets['Mostradores']['saldo_final'] ?? 0, 0), 1, 0, 'C', $isPlanta);
            $fpdf->Cell(28, 7, number_format($r->assets['Cooler']['saldo_final'] ?? 0, 0), 1, 0, 'C', $isPlanta);
            $fpdf->Cell(30, 7, number_format($r->assets['Bidones']['saldo_final'] ?? 0, 0), 1, 0, 'C', $isPlanta);

            $fpdf->SetFont('Montserrat', 'B', 8.5);
            $fpdf->SetTextColor(2, 93, 166);
            $fpdf->Cell(32, 7, number_format($r->total_assets, 0), 1, 1, 'C', $isPlanta);
        }

        // Subtotales y Gran Total
        $fpdf->SetDrawColor(200, 215, 230);

        // 1. Subtotal Planta
        $fpdf->SetFillColor(240, 247, 255);
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(97, 7, utf8_decode('DISPONIBLE EN ALMACÉN (PLANTA)'), 1, 0, 'R', true);
        $fpdf->Cell(30, 7, number_format($plantaTotals['Exhibidores'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(30, 7, number_format($plantaTotals['Congeladores'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(30, 7, number_format($plantaTotals['Mostradores'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(28, 7, number_format($plantaTotals['Cooler'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(30, 7, number_format($plantaTotals['Bidones'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(32, 7, number_format(array_sum($plantaTotals), 0), 1, 1, 'C', true);

        // 2. Subtotal Clientes
        $fpdf->SetFillColor(245, 247, 250);
        $fpdf->SetFont('Montserrat', 'B', 8);
        $fpdf->SetTextColor(60, 60, 60);
        $fpdf->Cell(97, 7, utf8_decode('TOTAL PRESTADOS EN CLIENTES'), 1, 0, 'R', true);
        $fpdf->Cell(30, 7, number_format($clientsTotals['Exhibidores'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(30, 7, number_format($clientsTotals['Congeladores'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(30, 7, number_format($clientsTotals['Mostradores'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(28, 7, number_format($clientsTotals['Cooler'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(30, 7, number_format($clientsTotals['Bidones'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(32, 7, number_format(array_sum($clientsTotals), 0), 1, 1, 'C', true);

        // 3. Gran Total Empresa
        $fpdf->SetFillColor(2, 93, 166);
        $fpdf->SetDrawColor(2, 93, 166);
        $fpdf->SetFont('Montserrat', 'B', 8.5);
        $fpdf->SetTextColor(255, 255, 255);
        $fpdf->Cell(97, 8, utf8_decode('TOTAL GENERAL EN LA EMPRESA (PLANTA + CLIENTES)'), 1, 0, 'R', true);
        $fpdf->Cell(30, 8, number_format($totals['Exhibidores'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(30, 8, number_format($totals['Congeladores'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(30, 8, number_format($totals['Mostradores'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(28, 8, number_format($totals['Cooler'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(30, 8, number_format($totals['Bidones'] ?? 0, 0), 1, 0, 'C', true);
        $fpdf->Cell(32, 8, number_format($grandTotal, 0), 1, 1, 'C', true);

        // Pie de página
        $fpdf->Ln(6);
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->SetTextColor(100, 100, 100);
        $fpdf->Cell(277, 5, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i') . ' | Subuz ERP'), 0, 1, 'R');

        $filename = "Resumen_Activos_Clientes_" . now()->format('dmY_Hi') . ".pdf";
        if (ob_get_level() > 0) ob_end_clean();
        return response($fpdf->Output('S', $filename), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    public function clientAssetsDetailedPdf(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $clientId = $request->client_id;
        $assetFilter = $request->asset_type;

        $matrix = $this->getClientAssetsMatrix($startDate, $endDate, $clientId, $assetFilter);
        $rows = $matrix['rows'];

        $fpdf = new Fpdf('P', 'mm', 'A4');
        $fpdf->AddPage();
        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');

        // Logo
        if (file_exists(public_path('assets/images/logo.jpg'))) {
            $fpdf->Image(public_path('assets/images/logo.jpg'), 10, 10, 28);
        }

        $fpdf->SetFont('Montserrat', 'B', 14);
        $fpdf->SetTextColor(2, 93, 166);
        $fpdf->Cell(190, 7, utf8_decode('SUBUZ S.A.C.'), 0, 1, 'C');

        $fpdf->SetFont('Montserrat', 'B', 11);
        $fpdf->SetTextColor(40, 40, 40);
        $fpdf->Cell(190, 6, utf8_decode('REPORTE DETALLADO DE ACTIVOS Y BIDONES POR CLIENTE'), 0, 1, 'C');

        $period = "Historial al " . now()->format('d/m/Y');
        if ($startDate && $endDate) {
            $period = "Periodo: " . date('d/m/Y', strtotime($startDate)) . " al " . date('d/m/Y', strtotime($endDate));
        } elseif ($startDate) {
            $period = "Desde: " . date('d/m/Y', strtotime($startDate));
        } elseif ($endDate) {
            $period = "Hasta: " . date('d/m/Y', strtotime($endDate));
        }

        $fpdf->SetFont('Montserrat', '', 8.5);
        $fpdf->SetTextColor(100, 100, 100);
        $fpdf->Cell(190, 5, utf8_decode($period), 0, 1, 'C');
        $fpdf->Ln(5);

        if ($rows->isEmpty()) {
            $fpdf->SetFont('Montserrat', 'B', 10);
            $fpdf->SetTextColor(100, 100, 100);
            $fpdf->Cell(190, 15, utf8_decode('No se encontraron registros para los filtros seleccionados.'), 1, 1, 'C');
            $fpdf->Ln(4);
        } else {
            foreach ($rows as $clientRow) {
                if ($fpdf->GetY() > 240) {
                    $fpdf->AddPage();
                }

                $isPlanta = $clientRow->is_planta;

                // Tarjeta de Cabecera
                $fpdf->SetFillColor(2, 93, 166);
                $fpdf->SetTextColor(255, 255, 255);
                $fpdf->SetFont('Montserrat', 'B', 9.5);
                $clientBadge = $isPlanta ? '[ALMACÉN CENTRAL - PLANTA]' : 'CLIENTE';
                $fpdf->Cell(190, 7, utf8_decode(" {$clientBadge}: {$clientRow->client_name}"), 0, 1, 'L', true);

                // Metadatos
                $fpdf->SetFillColor(245, 247, 250);
                $fpdf->SetTextColor(60, 60, 60);
                $fpdf->SetFont('Montserrat', '', 8);
                if ($isPlanta) {
                    $fpdf->Cell(190, 5, utf8_decode(" Ubicación: Almacén Principal Subuz  |  Control de Stock Físico Disponible"), 0, 1, 'L', true);
                } else {
                    $docInfo = $clientRow->client_document ? "Doc: {$clientRow->client_document}" : "Doc: S/N";
                    $telInfo = $clientRow->client_phone ? "Tel: {$clientRow->client_phone}" : "Tel: -";
                    $dirInfo = $clientRow->client_address ? "Dir: {$clientRow->client_address}" : "Dir: -";
                    $fpdf->Cell(190, 5, utf8_decode(" {$docInfo}  |  {$telInfo}  |  {$dirInfo}"), 0, 1, 'L', true);
                }
                $fpdf->Ln(1);

                // Obtener movimientos según sea Planta o Cliente
                if ($isPlanta) {
                    $cQuery = InventoryMovement::with(['dispatcher', 'user', 'client'])
                        ->where(function ($q) {
                            $q->where('item_type', 'client_asset')
                              ->orWhereNotNull('client_id');
                        });
                } else {
                    $cQuery = InventoryMovement::with(['dispatcher', 'user', 'client'])
                        ->where('client_id', $clientRow->client_id);
                }

                if ($startDate) {
                    $cQuery->where(function ($q) use ($startDate) {
                        $q->whereDate('date', '>=', $startDate)
                          ->orWhere(function ($sq) use ($startDate) {
                              $sq->whereNull('date')->whereDate('created_at', '>=', $startDate);
                          });
                    });
                }
                if ($endDate) {
                    $cQuery->where(function ($q) use ($endDate) {
                        $q->whereDate('date', '<=', $endDate)
                          ->orWhere(function ($sq) use ($endDate) {
                              $sq->whereNull('date')->whereDate('created_at', '<=', $endDate);
                          });
                    });
                }

                $rawMovs = $cQuery->get()->filter(function ($m) use ($assetFilter) {
                    $norm = $this->normalizeAssetType($m->item_name);
                    if ($assetFilter) return $norm === $assetFilter;
                    return in_array($norm, self::$clientAssetTypes);
                });

                if ($isPlanta) {
                    $movs = $rawMovs->map(function ($m) use ($clientRow) {
                        $isDirect = ($m->client_id == $clientRow->client_id);
                        $cName = $m->client ? $m->client->name : 'Cliente';
                        if ($isDirect) {
                            if ($m->movement_type === 'initial_balance') $tLabel = 'Saldo Inicial Almacén';
                            elseif (in_array($m->movement_type, ['income', 'return'])) $tLabel = 'Ingreso a Planta (+)';
                            else $tLabel = 'Salida de Planta (-)';
                            $note = $m->notes ?: '-';
                        } else {
                            if (in_array($m->movement_type, ['delivery', 'income'])) {
                                $tLabel = 'Salida a Cliente (-)';
                                $note = "Entrega a {$cName}" . ($m->notes ? " | {$m->notes}" : '');
                            } elseif (in_array($m->movement_type, ['return', 'outcome', 'withdrawal'])) {
                                $tLabel = 'Devolución de Cliente (+)';
                                $note = "Devolución de {$cName}" . ($m->notes ? " | {$m->notes}" : '');
                            } else {
                                return null;
                            }
                        }
                        $m->custom_label = $tLabel;
                        $m->custom_note = $note;
                        return $m;
                    })->filter()->sortBy(function ($m) {
                        $d = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : '1970-01-01');
                        return $d . '_' . str_pad($m->id, 10, '0', STR_PAD_LEFT);
                    });
                } else {
                    $movs = $rawMovs->map(function ($m) {
                        $tLabel = 'Saldo Inicial';
                        if (in_array($m->movement_type, ['income', 'delivery'])) $tLabel = 'Entrega (+)';
                        if (in_array($m->movement_type, ['outcome', 'return', 'withdrawal'])) $tLabel = 'Devolución (-)';
                        $m->custom_label = $tLabel;
                        $m->custom_note = $m->notes ?: '-';
                        return $m;
                    })->sortBy(function ($m) {
                        $d = $m->date ? $m->date->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : '1970-01-01');
                        return $d . '_' . str_pad($m->id, 10, '0', STR_PAD_LEFT);
                    });
                }

                // Cabecera de movimientos
                $fpdf->SetFillColor(220, 230, 242);
                $fpdf->SetTextColor(2, 93, 166);
                $fpdf->SetFont('Montserrat', 'B', 7.5);
                $fpdf->Cell(22, 6, utf8_decode('FECHA'), 1, 0, 'C', true);
                $fpdf->Cell(32, 6, utf8_decode('ACTIVO'), 1, 0, 'C', true);
                $fpdf->Cell(34, 6, utf8_decode('MOVIMIENTO'), 1, 0, 'C', true);
                $fpdf->Cell(14, 6, utf8_decode('CANT.'), 1, 0, 'C', true);
                $fpdf->Cell(32, 6, utf8_decode('DESPACHADOR'), 1, 0, 'C', true);
                $fpdf->Cell(56, 6, utf8_decode('OBSERVACIÓN / REF.'), 1, 1, 'L', true);

                $fpdf->SetFont('Montserrat', '', 7.5);
                $fpdf->SetTextColor(30, 30, 30);

                if ($movs->isEmpty()) {
                    $fpdf->Cell(190, 6, utf8_decode('Sin movimientos registrados en este periodo'), 1, 1, 'C');
                } else {
                    foreach ($movs as $m) {
                        if ($fpdf->GetY() > 265) {
                            $fpdf->AddPage();
                        }

                        $mDate = $m->date ? $m->date->format('d/m/Y') : ($m->created_at ? $m->created_at->format('d/m/Y') : '-');
                        $assetName = $this->normalizeAssetType($m->item_name);
                        $dispName = $m->dispatcher ? $m->dispatcher->name : '-';
                        if (strlen($dispName) > 18) $dispName = substr($dispName, 0, 16) . '..';
                        $note = $m->custom_note ?: '-';
                        if (strlen($note) > 34) $note = substr($note, 0, 32) . '..';

                        $fpdf->Cell(22, 5.5, $mDate, 1, 0, 'C');
                        $fpdf->Cell(32, 5.5, utf8_decode($assetName), 1, 0, 'L');
                        $fpdf->Cell(34, 5.5, utf8_decode($m->custom_label), 1, 0, 'C');
                        $fpdf->Cell(14, 5.5, number_format($m->quantity, 0), 1, 0, 'C');
                        $fpdf->Cell(32, 5.5, utf8_decode($dispName), 1, 0, 'L');
                        $fpdf->Cell(56, 5.5, utf8_decode($note), 1, 1, 'L');
                    }
                }

                // Resumen de saldos finales
                $fpdf->SetFillColor(240, 245, 250);
                $fpdf->SetFont('Montserrat', 'B', 7.5);
                $fpdf->SetTextColor(2, 93, 166);
                $saldoLabel = $isPlanta ? 'STOCK DISPONIBLE EN ALMACÉN' : 'SALDO EN CUSTODIA DEL CLIENTE';

                if ($assetFilter) {
                    $filtVal = $clientRow->assets[$assetFilter]['saldo_final'] ?? 0;
                    $summaryStr = "{$saldoLabel}:  {$assetFilter}: {$filtVal}  |  TOTAL: {$clientRow->total_assets}";
                } else {
                    $exVal = $clientRow->assets['Exhibidores']['saldo_final'] ?? 0;
                    $cgVal = $clientRow->assets['Congeladores']['saldo_final'] ?? 0;
                    $moVal = $clientRow->assets['Mostradores']['saldo_final'] ?? 0;
                    $coVal = $clientRow->assets['Cooler']['saldo_final'] ?? 0;
                    $biVal = $clientRow->assets['Bidones']['saldo_final'] ?? 0;
                    $summaryStr = "{$saldoLabel}:  Exhibidores: {$exVal}  |  Congeladores: {$cgVal}  |  Mostradores: {$moVal}  |  Cooler: {$coVal}  |  Bidones: {$biVal}  |  TOTAL: {$clientRow->total_assets}";
                }
                $fpdf->Cell(190, 6, utf8_decode($summaryStr), 1, 1, 'R', true);

                $fpdf->Ln(4);
            }
        }

        // Pie de página
        $fpdf->SetFont('Montserrat', '', 8);
        $fpdf->SetTextColor(100, 100, 100);
        $fpdf->Cell(190, 5, utf8_decode('Generado el: ' . now()->format('d/m/Y H:i') . ' | Subuz ERP'), 0, 1, 'R');

        $filename = "Detallado_Activos_Clientes_" . now()->format('dmY_Hi') . ".pdf";
        if (ob_get_level() > 0) ob_end_clean();
        return response($fpdf->Output('S', $filename), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
