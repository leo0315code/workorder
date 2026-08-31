<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 工单标签：tags（标签定义，name 唯一）+ ticket_tag（多对多关联）
 * 客服在详情页打标签/移除；列表页可按标签筛选。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->unique();
            $table->string('color', 20)->default('indigo'); // 徽标配色 key
            $table->timestamps();
        });

        Schema::create('ticket_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
            $table->unique(['ticket_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_tag');
        Schema::dropIfExists('tags');
    }
};
