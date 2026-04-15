<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashboxMovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cashbox_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cashbox_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->enum('type', ['paid', 'debt']);
            $table->decimal('amount', 10, 2);
            $table->dateTime('date');
            $table->text('note')->nullable();

            $table->index('cashbox_id');
            $table->index('sale_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cashbox_movements');
    }
}
