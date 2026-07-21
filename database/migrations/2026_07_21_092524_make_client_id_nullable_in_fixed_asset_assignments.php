<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeClientIdNullableInFixedAssetAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fixed_asset_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->change();
            $table->string('assignment_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fixed_asset_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable(false)->change();
            $table->string('assignment_type')->nullable(false)->change();
        });
    }
}
