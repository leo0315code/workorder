<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 知识库：文章分类 + 文章
 * 分类：名称 + 排序 + 启用；文章：标题/正文/分类/发布状态/浏览数/作者
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort']);
        });

        Schema::create('kb_articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kb_category_id')->nullable()->index();
            $table->foreign('kb_category_id')->references('id')->on('kb_categories')->nullOnDelete();
            $table->string('title', 200);
            $table->text('content');
            $table->boolean('is_published')->default(true);
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_articles');
        Schema::dropIfExists('kb_categories');
    }
};
