<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('branch_staff', 'email')) {
            Schema::table('branch_staff', function (Blueprint $table) {
                $table->string('email')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('branch_staff', 'email')) {
            Schema::table('branch_staff', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }
    }
};
