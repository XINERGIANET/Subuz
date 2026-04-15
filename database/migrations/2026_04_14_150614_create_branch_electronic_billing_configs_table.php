<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchElectronicBillingConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branch_electronic_billing_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable(); // Para compatibilidad con el repo origen
            $table->string('provider')->default('apisunat');
            $table->boolean('enabled')->default(true);
            $table->string('api_url')->nullable();
            $table->string('persona_id')->nullable();
            $table->text('persona_token')->nullable();
            $table->string('series_boleta', 10)->default('B001');
            $table->string('series_factura', 10)->default('F001');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('branch_electronic_billing_configs');
    }
}
