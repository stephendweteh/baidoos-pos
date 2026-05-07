<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMtnMomoFieldsToSalesTable extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('success')->after('payment_method');
            $table->string('payment_reference', 100)->nullable()->after('payment_status');
            $table->string('momo_status', 30)->nullable()->after('payment_reference');
            $table->string('payer_msisdn', 20)->nullable()->after('momo_status');

            $table->index('payment_status');
            $table->index('payment_reference');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['payment_reference']);
            $table->dropColumn(['payment_status', 'payment_reference', 'momo_status', 'payer_msisdn']);
        });
    }
}
