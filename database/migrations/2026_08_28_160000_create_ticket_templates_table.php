<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 工单模板表：预填 subject/description，可绑定分类与产品，sort 控制顺序。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('subject', 255);
            $table->text('description');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('priority', 10)->default('normal');
            $table->boolean('is_active')->default(true);
            $table->integer('sort')->default(0);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_templates');
    }
};
