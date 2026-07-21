<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFixedAssetAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fixed_asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixed_asset_id');
            $table->unsignedBigInteger('client_id');
            $table->date('assigned_date');
            $table->date('returned_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Foreign keys if necessary
            // $table->foreign('fixed_asset_id')->references('id')->on('fixed_assets')->onDelete('cascade');
            // $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fixed_asset_assignments');
    }
}
