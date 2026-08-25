<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('supplies')) {
            Schema::table('supplies', function (Blueprint $table) {
                if (!Schema::hasColumn('supplies', 'allowed_for_dispatchers')) {
                    $table->boolean('allowed_for_dispatchers')->default(false)->after('unit');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'allowed_for_dispatchers')) {
                    $table->boolean('allowed_for_dispatchers')->default(false)->after('is_loanable');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('supplies')) {
            Schema::table('supplies', function (Blueprint $table) {
                if (Schema::hasColumn('supplies', 'allowed_for_dispatchers')) {
                    $table->dropColumn('allowed_for_dispatchers');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'allowed_for_dispatchers')) {
                    $table->dropColumn('allowed_for_dispatchers');
                }
            });
        }
    }
};
