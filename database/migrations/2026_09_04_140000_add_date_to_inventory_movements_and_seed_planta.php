<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar columna 'date' si no existe
        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_movements', 'date')) {
                    $table->date('date')->nullable()->after('dispatcher_id')->index();
                }
            });

            // Llenar registros existentes con la fecha de created_at si date está nulo
            try {
                DB::statement("UPDATE inventory_movements SET date = DATE(created_at) WHERE date IS NULL AND created_at IS NOT NULL");
            } catch (\Exception $e) {
                // Ignore if fails
            }
        }

        // 2. Asegurar que exista el cliente PLANTA
        if (Schema::hasTable('clients')) {
            $plantaExists = DB::table('clients')
                ->whereRaw('LOWER(TRIM(name)) = ?', ['planta'])
                ->orWhereRaw('LOWER(TRIM(name)) = ?', ['planta sub-uz'])
                ->orWhereRaw('LOWER(TRIM(name)) = ?', ['planta (sede principal)'])
                ->exists();

            if (!$plantaExists) {
                DB::table('clients')->insert([
                    'name' => 'PLANTA (Sede Principal)',
                    'document' => '00000000',
                    'type' => 'DNI',
                    'phone' => '920488526',
                    'address' => 'Planta Principal Subuz',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                if (Schema::hasColumn('inventory_movements', 'date')) {
                    $table->dropColumn('date');
                }
            });
        }
    }
};
