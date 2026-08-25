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
        if (Schema::hasTable('expense_subcategories')) {
            Schema::table('expense_subcategories', function (Blueprint $table) {
                if (!Schema::hasColumn('expense_subcategories', 'allowed_for_dispatchers')) {
                    $table->boolean('allowed_for_dispatchers')->default(false)->after('name');
                }
            });
        }

        if (Schema::hasTable('fixed_assets')) {
            Schema::table('fixed_assets', function (Blueprint $table) {
                if (!Schema::hasColumn('fixed_assets', 'allowed_for_dispatchers')) {
                    $table->boolean('allowed_for_dispatchers')->default(false)->after('category');
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
        if (Schema::hasTable('expense_subcategories')) {
            Schema::table('expense_subcategories', function (Blueprint $table) {
                if (Schema::hasColumn('expense_subcategories', 'allowed_for_dispatchers')) {
                    $table->dropColumn('allowed_for_dispatchers');
                }
            });
        }

        if (Schema::hasTable('fixed_assets')) {
            Schema::table('fixed_assets', function (Blueprint $table) {
                if (Schema::hasColumn('fixed_assets', 'allowed_for_dispatchers')) {
                    $table->dropColumn('allowed_for_dispatchers');
                }
            });
        }
    }
};

