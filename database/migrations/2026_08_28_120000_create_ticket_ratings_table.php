<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 客户满意度（CSAT）表：rating 1-5 星，is_solved 是否解决问题，comment 评语。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->comment('评分人')->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('rating')->comment('1-5 星');
            $table->string('comment')->nullable();
            $table->timestamps();

            $table->unique('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_ratings');
    }
};
