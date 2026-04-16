<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFacturaAndBoletaCountsToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'factura_count')) {
                $table->unsignedInteger('factura_count')->default(0);
            }
            if (!Schema::hasColumn('settings', 'boleta_count')) {
                $table->unsignedInteger('boleta_count')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['factura_count', 'boleta_count']);
        });
    }
}
