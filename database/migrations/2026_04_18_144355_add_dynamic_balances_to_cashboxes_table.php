<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDynamicBalancesToCashboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('cashboxes', function (Blueprint $table) {
            $table->text('opening_balances')->nullable()->after('opening_amount');
            $table->text('closing_balances')->nullable()->after('closing_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashboxes', function (Blueprint $table) {
            $table->dropColumn(['opening_balances', 'closing_balances']);
        });
    }

}
