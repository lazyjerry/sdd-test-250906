<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique()->comment('用戶名（唯一）');
            $table->string('password')->comment('密碼');
            $table->string('email')->nullable()->comment('電子郵件（可選）');
            $table->string('name')->comment('顯示名稱');
            $table->json('permissions')->nullable()->comment('權限列表');
            $table->timestamp('last_login_at')->nullable()->comment('最後登入時間');
            $table->timestamp('email_verified_at')->nullable()->comment('郵件驗證時間');
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index('username');
            $table->index('email');
            $table->index('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_users');
    }
};
