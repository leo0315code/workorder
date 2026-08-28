<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_ratings', function (Blueprint $table) {
            // 客户评价时选择：问题是否已解决（null=未选择，0=未解决，1=已解决）
            $table->boolean('is_solved')->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_ratings', function (Blueprint $table) {
            $table->dropColumn('is_solved');
        });
    }
};
