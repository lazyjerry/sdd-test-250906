<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 為 phone 欄位添加 unique 約束
     * 注意: phone 可能為 nullable，需要允許多個 NULL 值
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 為 phone 添加 unique 約束
            // MySQL 允許多個 NULL 值，所以 nullable 欄位的 unique 約束是安全的
            $table->unique('phone', 'users_phone_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_phone_unique');
        });
    }
};
