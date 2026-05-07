<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerContactToSalesTable extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('customer_phone', 20)->nullable()->after('customer_name');
            $table->string('customer_email', 150)->nullable()->after('customer_phone');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['customer_phone', 'customer_email']);
        });
    }
}
