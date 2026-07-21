<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRentalOptionsToFixedAssetAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fixed_asset_assignments', function (Blueprint $table) {
            $table->string('payment_frequency')->nullable()->after('amount'); // diario, semanal, quincenal, mensual
            $table->string('rental_mode')->default('indefinite')->after('payment_frequency'); // indefinite, fixed
            $table->integer('total_installments')->nullable()->after('rental_mode');
        });

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_installments');
        
        Schema::table('fixed_asset_assignments', function (Blueprint $table) {
            $table->dropColumn(['payment_frequency', 'rental_mode', 'total_installments']);
        });
    }
}
