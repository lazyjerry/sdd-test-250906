<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * 添加必要的索引以優化查詢性能
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. role 欄位索引 - 頻繁的角色查詢
            // 查詢: where('role', 'admin'), whereIn('role', ['admin', 'super_admin'])
            $table->index('role', 'users_role_index');

            // 2. email_verified_at 欄位索引 - 驗證狀態查詢
            // 查詢: whereNotNull('email_verified_at'), whereNull('email_verified_at')
            $table->index('email_verified_at', 'users_email_verified_at_index');

            // 3. created_at 欄位索引 - 日期範圍查詢和統計
            // 查詢: whereDate('created_at', today()), where('created_at', '>=', ...)
            $table->index('created_at', 'users_created_at_index');

            // 4. deleted_at 欄位索引 - 軟刪除查詢優化
            // SoftDeletes trait 會頻繁查詢此欄位
            $table->index('deleted_at', 'users_deleted_at_index');

            // 5. last_login_at 欄位索引 - 登入統計和排序
            // 可能的查詢: orderBy('last_login_at'), where('last_login_at', '>', ...)
            $table->index('last_login_at', 'users_last_login_at_index');

            // 6. 複合索引：role + deleted_at - 優化軟刪除的角色查詢
            // 查詢: where('role', 'admin')->whereNull('deleted_at')
            $table->index(['role', 'deleted_at'], 'users_role_deleted_at_index');

            // 7. 複合索引：email_verified_at + created_at - 驗證狀態統計
            // 查詢: whereNotNull('email_verified_at')->whereDate('created_at', today())
            $table->index(['email_verified_at', 'created_at'], 'users_verification_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 移除單欄位索引
            $table->dropIndex('users_role_index');
            $table->dropIndex('users_email_verified_at_index');
            $table->dropIndex('users_created_at_index');
            $table->dropIndex('users_deleted_at_index');
            $table->dropIndex('users_last_login_at_index');

            // 移除複合索引
            $table->dropIndex('users_role_deleted_at_index');
            $table->dropIndex('users_verification_created_index');
        });
    }
};
