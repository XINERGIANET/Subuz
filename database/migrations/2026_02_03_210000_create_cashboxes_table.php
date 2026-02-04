<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cashboxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opened_by');
            $table->dateTime('opened_at');
            $table->decimal('opening_amount', 10, 2)->default(0);
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->decimal('closing_amount', 10, 2)->nullable();
            $table->boolean('is_open')->default(true);
            $table->text('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cashboxes');
    }
}
