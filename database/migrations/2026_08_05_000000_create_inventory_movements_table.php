<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 0. Asegurar la estructura de la tabla de migraciones de Laravel
        if (Schema::hasTable('migrations')) {
            try {
                $migCols = DB::select("DESCRIBE migrations");
                $migIdCol = collect($migCols)->firstWhere('Field', 'id');
                if ($migIdCol && strpos(strtolower($migIdCol->Extra ?? ''), 'auto_increment') === false) {
                    DB::statement("ALTER TABLE migrations MODIFY id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY;");
                }
            } catch (\Exception $e) {
                try {
                    DB::statement("ALTER TABLE migrations MODIFY id INT UNSIGNED AUTO_INCREMENT;");
                } catch (\Exception $ex) {}
            }
        }

        // 1. Crear la tabla de movimientos Kardex si no existe
        if (!Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('item_type'); // 'supply', 'fixed_asset', 'product'
                $table->unsignedBigInteger('item_id')->nullable();
                $table->string('item_name')->nullable();
                $table->string('movement_type'); // 'initial_balance', 'income', 'outcome', 'adjustment'
                $table->decimal('quantity', 12, 2)->default(0);
                $table->string('notes')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        // 2. Asegurar la estructura adecuada en la tabla supplies
        if (Schema::hasTable('supplies')) {
            try {
                $columns = DB::select("DESCRIBE supplies");
                $idCol = collect($columns)->firstWhere('Field', 'id');
                if ($idCol && strpos(strtolower($idCol->Extra ?? ''), 'auto_increment') === false) {
                    DB::statement("ALTER TABLE supplies MODIFY id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY;");
                }
            } catch (\Exception $e) {
                try {
                    DB::statement("ALTER TABLE supplies MODIFY id BIGINT UNSIGNED AUTO_INCREMENT;");
                } catch (\Exception $ex) {}
            }
        }

        // 3. Sembrar los insumos estándar si no existen en la base de datos
        $standardSupplies = [
            ['name' => 'Bidones 20L', 'unit' => 'Unidades'],
            ['name' => 'Tapas', 'unit' => 'Unidades'],
            ['name' => 'Sellos', 'unit' => 'Unidades'],
            ['name' => 'Etiquetas', 'unit' => 'Unidades'],
            ['name' => 'Bolsas 2kg', 'unit' => 'Paquetes'],
            ['name' => 'Bolsas 3kg', 'unit' => 'Paquetes'],
            ['name' => 'Bolsas 5kg', 'unit' => 'Paquetes'],
        ];

        foreach ($standardSupplies as $s) {
            $exists = DB::table('supplies')->where('name', $s['name'])->exists();
            if (!$exists) {
                DB::table('supplies')->insert([
                    'name' => $s['name'],
                    'stock' => 0.00,
                    'unit' => $s['unit'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
