<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRentalFieldsToFixedAssetsTable extends Migration
{
    public function up()
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->string('internal_code')->nullable()->after('category');
        });

        Schema::table('fixed_asset_assignments', function (Blueprint $table) {
            $table->string('assignment_type')->default('prestado')->after('client_id'); // prestado, alquilado
            $table->decimal('amount', 10, 2)->nullable()->after('assignment_type'); // rental amount
        });
    }

    public function down()
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropColumn('internal_code');
        });

        Schema::table('fixed_asset_assignments', function (Blueprint $table) {
            $table->dropColumn(['assignment_type', 'amount']);
        });
    }
}
