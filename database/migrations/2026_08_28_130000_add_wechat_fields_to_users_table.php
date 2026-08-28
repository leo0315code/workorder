<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 微信登录绑定（开放平台扫码 / 公众号网页授权）
            $table->string('wechat_openid')->nullable()->unique()->after('avatar');
            $table->string('wechat_unionid')->nullable()->after('wechat_openid');
            $table->string('wechat_nickname')->nullable()->after('wechat_unionid');
            $table->string('wechat_avatar')->nullable()->after('wechat_nickname');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wechat_openid', 'wechat_unionid', 'wechat_nickname', 'wechat_avatar']);
        });
    }
};
