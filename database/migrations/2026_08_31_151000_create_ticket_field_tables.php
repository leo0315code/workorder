<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 工单自定义字段：字段定义 + 工单值
 * 管理员在「工单字段」配置字段（文本/数字/下拉/日期），
 * 创建工单时按需填写，详情页展示。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_field_defs', function (Blueprint $table) {
            $table->id();
            $table->string('label', 50);                    // 显示名
            $table->string('key', 50)->unique();            // 表单字段 key（slug）
            $table->string('type', 20)->default('text');    // text | number | select | date
            $table->json('options')->nullable();            // select 选项数组
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort']);
        });

        Schema::create('ticket_field_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('field_def_id');
            $table->string('value', 500)->default('');

            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('field_def_id')->references('id')->on('ticket_field_defs')->cascadeOnDelete();
            $table->unique(['ticket_id', 'field_def_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_field_values');
        Schema::dropIfExists('ticket_field_defs');
    }
};
