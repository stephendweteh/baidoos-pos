<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddMobileMoneyToPaymentMethodEnum extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE `sales` MODIFY `payment_method` ENUM('cash','transfer','card','mtn_momo','mobile_money') NOT NULL DEFAULT 'cash'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE `sales` MODIFY `payment_method` ENUM('cash','transfer','card','mtn_momo') NOT NULL DEFAULT 'cash'");
    }
}
