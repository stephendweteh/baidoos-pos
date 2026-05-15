<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStockQuantityToItemsTable extends Migration
{
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'stock_quantity')) {
                $table->unsignedInteger('stock_quantity')->nullable()->after('type');
            }
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'stock_quantity')) {
                $table->dropColumn('stock_quantity');
            }
        });
    }
}
