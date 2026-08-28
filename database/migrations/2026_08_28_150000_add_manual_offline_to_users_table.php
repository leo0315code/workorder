<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 管理员手动置为离线（请假/挂机时不参与自动分配，即使 WS 在线）
            $table->boolean('manual_offline')->default(false)->after('wechat_avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('manual_offline');
        });
    }
};
