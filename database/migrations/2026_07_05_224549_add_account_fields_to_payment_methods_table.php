<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountFieldsToPaymentMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('account_number')->nullable();
            $table->string('holder_name')->nullable();
            $table->boolean('show_in_reports')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['account_number', 'holder_name', 'show_in_reports']);
        });
    }
}
