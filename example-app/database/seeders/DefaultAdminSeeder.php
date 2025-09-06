<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 預設管理員用戶 Seeder.
 *
 * 創建系統初始化時的預設管理員用戶
 * 用於系統首次部署後的管理員登入
 */
class DefaultAdminSeeder extends Seeder
{
    /**
     * 運行 seeder.
     */
    public function run(): void
    {
        // 檢查是否已存在預設管理員
        $existingAdmin = User::where('username', 'admin')
            ->orWhere('email', 'admin@example.com')
            ->first();

        if ($existingAdmin) {
            $this->command->info('預設管理員已存在，跳過創建。');

            return;
        }

        // 創建預設管理員用戶
        $admin = User::create([
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'email' => 'admin@example.com', // 給管理員一個 email
            'name' => '系統管理員',
            'role' => 'super_admin',
            'permissions' => [
                'manage_users',
                'manage_system',
                'create_admins',
                'view_all_data',
                'view_reports',
                'manage_settings',
                'system_maintenance',
                'user_management',
                'audit_logs'
            ],
            'last_login_at' => null,
            'email_verified_at' => now(), // 預設管理員自動驗證
        ]);

        $this->command->info('預設管理員用戶已創建：');
        $this->command->info('用戶名: admin');
        $this->command->info('密碼: admin123');
        $this->command->info('請在生產環境中立即更改此密碼！');
        $this->command->info("管理員 ID: {$admin->id}");
    }
}
