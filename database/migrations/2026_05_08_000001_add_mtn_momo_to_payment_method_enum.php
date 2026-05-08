<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddMtnMomoToPaymentMethodEnum extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE `sales` MODIFY `payment_method` ENUM('cash','transfer','card','mtn_momo') NOT NULL DEFAULT 'cash'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE `sales` MODIFY `payment_method` ENUM('cash','transfer','card') NOT NULL DEFAULT 'cash'");
    }
}
