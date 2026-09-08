<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('login_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email', 191)->nullable()->index();          // 登录尝试的账号（邮箱/手机号）
            $table->string('channel', 20)->default('web');             // web | admin | phone | wechat | api
            $table->string('ip', 45)->nullable()->index();             // 客户端 IP（IPv6 兼容）
            $table->string('user_agent', 255)->nullable();
            $table->boolean('success')->default(false)->index();       // 成功/失败
            $table->string('reason', 50)->nullable();                  // 失败原因：bad_credentials / not_agent / code_invalid / rate_limited 等
            $table->timestamp('login_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_audits');
    }
};
