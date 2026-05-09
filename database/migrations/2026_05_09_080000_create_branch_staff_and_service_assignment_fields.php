<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->boolean('assign_staff')->default(false)->after('type');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('branch_staff_id')->nullable()->after('item_id')->constrained('branch_staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_staff_id');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('assign_staff');
        });

        Schema::dropIfExists('branch_staff');
    }
};