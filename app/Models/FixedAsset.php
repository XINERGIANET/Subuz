<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'allowed_for_dispatchers',
        'internal_code',
        'serial_number',
        'status',
        'purchase_date',
        'purchase_cost',
        'payment_method_id',
        'voucher_number',
        'current_client_id',
        'notes',
    ];

    protected $casts = [
        'allowed_for_dispatchers' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'current_client_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function assignments()
    {
        return $this->hasMany(FixedAssetAssignment::class);
    }

    public function getHistoryExpensesAttribute()
    {
        return \App\Models\Expense::where(function($query) {
                $query->where('description', 'like', '%(' . $this->name . ')%')
                      ->orWhere('description', 'like', '%Activo Fijo: ' . $this->name . '%');
            })
            ->orderBy('real_date', 'desc')
            ->get();
    }

    public function getHistoryIncomesAttribute()
    {
        return \App\Models\CashboxMovement::where('note', 'like', '%Cobro de Alquiler: ' . $this->name . '%')
            ->where('type', 'income')
            ->orderBy('date', 'desc')
            ->get();
    }

    public function getClientSalesStatsAttribute()
    {
        if (!$this->current_client_id) {
            return null;
        }

        $client = $this->client;
        if (!$client) {
            return null;
        }

        $salesQuery = \App\Models\Sale::where('client_id', $this->current_client_id)
            ->where('status', '!=', 'Anulado');

        $totalSalesAmount = (float) (clone $salesQuery)->sum('total');
        $totalSalesCount = (int) (clone $salesQuery)->count();
        $firstSale = (clone $salesQuery)->orderBy('date', 'asc')->first();
        $lastSale = (clone $salesQuery)->orderBy('date', 'desc')->first();

        // Ventas generadas desde la asignación del activo
        $activeAssignment = $this->assignments()->whereNull('returned_date')->latest()->first();
        $assignedDate = $activeAssignment && $activeAssignment->assigned_date ? $activeAssignment->assigned_date : null;

        $salesSinceAssigned = 0;
        $salesCountSinceAssigned = 0;
        if ($assignedDate) {
            $sinceQuery = (clone $salesQuery)->whereDate('date', '>=', $assignedDate);
            $salesSinceAssigned = (float) $sinceQuery->sum('total');
            $salesCountSinceAssigned = (int) $sinceQuery->count();
        } else {
            $salesSinceAssigned = $totalSalesAmount;
            $salesCountSinceAssigned = $totalSalesCount;
        }

        $cost = (float) $this->purchase_cost;
        $roiPercentage = 0;
        if ($cost > 0) {
            $roiPercentage = min(1000, round(($salesSinceAssigned / $cost) * 100, 1));
        }

        $recentSales = (clone $salesQuery)->orderBy('date', 'desc')->limit(8)->get();

        return (object)[
            'client_name' => $client->name,
            'client_document' => $client->document,
            'assigned_date' => $assignedDate,
            'assignment_type' => $activeAssignment ? $activeAssignment->assignment_type : 'prestado',
            'total_sales_amount' => $totalSalesAmount,
            'total_sales_count' => $totalSalesCount,
            'sales_since_assigned' => $salesSinceAssigned,
            'sales_count_since_assigned' => $salesCountSinceAssigned,
            'first_sale_date' => $firstSale ? $firstSale->date : null,
            'last_sale_date' => $lastSale ? $lastSale->date : null,
            'cost' => $cost,
            'roi_percentage' => $roiPercentage,
            'is_roi_recovered' => ($cost > 0 && $salesSinceAssigned >= $cost) || ($cost == 0 && $salesSinceAssigned > 0),
            'recent_sales' => $recentSales,
        ];
    }
}
