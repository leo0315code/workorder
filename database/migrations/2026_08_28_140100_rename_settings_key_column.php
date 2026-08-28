<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // key 是 MySQL 保留字，改为 setting_key
        Schema::table('settings', function (Blueprint $table) {
            $table->renameColumn('key', 'setting_key');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->renameColumn('setting_key', 'key');
        });
    }
};
