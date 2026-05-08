<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMomoSalesToDayClosingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('day_closings', function (Blueprint $table) {
            $table->decimal('total_momo_sales', 12, 2)->default(0)->after('total_cash_sales');
            $table->dropColumn(['total_transfer_sales', 'total_card_sales']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('day_closings', function (Blueprint $table) {
            $table->decimal('total_transfer_sales', 12, 2)->default(0)->after('total_cash_sales');
            $table->decimal('total_card_sales', 12, 2)->default(0)->after('total_transfer_sales');
            $table->dropColumn('total_momo_sales');
        });
    }
}
