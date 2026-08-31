<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tickets 查询索引：列表筛选（status/priority/assignee）、SLA 巡检（sla_due_at）、报表（created_at）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
            $table->index('sla_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['sla_due_at']);
        });
    }
};
