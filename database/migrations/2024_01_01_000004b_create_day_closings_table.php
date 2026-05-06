<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDayClosingsTable extends Migration
{
    public function up()
    {
        Schema::create('day_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->date('closing_date');
            $table->decimal('opening_cash', 10, 2)->default(0);
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->decimal('total_cash_sales', 10, 2)->default(0);
            $table->decimal('total_transfer_sales', 10, 2)->default(0);
            $table->decimal('total_card_sales', 10, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->decimal('cash_counted', 10, 2)->default(0);
            $table->decimal('cash_variance', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'closing_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('day_closings');
    }
}
