<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFixedAssetInstallmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('fixed_asset_installments')) {
            Schema::create('fixed_asset_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_assignment_id')->constrained()->onDelete('cascade');
            $table->integer('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, paid
            $table->date('paid_date')->nullable();
            $table->foreignId('cashbox_movement_id')->nullable()->constrained('cashbox_movements')->onDelete('set null');
            $table->timestamps();
        });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fixed_asset_installments');
    }
}
