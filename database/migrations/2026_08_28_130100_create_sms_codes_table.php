<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 短信验证码表：expires_at 用于 5 分钟有效期，used_at 标记一次性使用，ip 记录发送来源。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 30)->index();
            $table->string('code', 10);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_codes');
    }
};
