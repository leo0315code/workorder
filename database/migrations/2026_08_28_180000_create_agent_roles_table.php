<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();       // slug：主管/接单员/只读
            $table->string('label', 50);                // 中文显示名
            $table->string('description', 255)->nullable();
            $table->json('modules');                    // 模块键数组
            $table->integer('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('agent_role_id')->nullable()->after('permissions')
                ->constrained('agent_roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agent_role_id');
        });
        Schema::dropIfExists('agent_roles');
    }
};
