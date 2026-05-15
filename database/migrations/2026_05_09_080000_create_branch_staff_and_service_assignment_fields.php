<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('branch_staff')) {
            Schema::create('branch_staff', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('items', 'assign_staff')) {
            Schema::table('items', function (Blueprint $table) {
                $table->boolean('assign_staff')->default(false)->after('type');
            });
        }

        if (!Schema::hasColumn('sale_items', 'branch_staff_id')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->foreignId('branch_staff_id')->nullable()->after('item_id')->constrained('branch_staff')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sale_items', 'branch_staff_id')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_staff_id');
            });
        }

        if (Schema::hasColumn('items', 'assign_staff')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('assign_staff');
            });
        }

        if (Schema::hasTable('branch_staff')) {
            Schema::drop('branch_staff');
        }
    }
};