<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // 业务编号，如 TK-20260828-0001
            $table->string('no')->unique();
            $table->foreignId('user_id')->comment('提交人/客户账号')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->comment('关联客户档案')->constrained('customers')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->comment('关联产品')->constrained('products')->nullOnDelete();
            $table->string('subject');
            $table->text('description')->nullable();

            // 优先级：low|normal|high|urgent
            $table->string('priority')->default('normal');
            // 状态：open|pending|in_progress|resolved|closed
            $table->string('status')->default('open');

            $table->foreignId('assignee_id')->nullable()->comment('处理客服')->constrained('users')->nullOnDelete();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
