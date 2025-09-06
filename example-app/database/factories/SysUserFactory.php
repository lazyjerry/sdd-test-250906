<?php

namespace Database\Factories;

use App\Models\SysUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SysUser>
 */
class SysUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SysUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'password' => Hash::make('password'),
            'email' => $this->faker->unique()->safeEmail(),
            'name' => $this->faker->name(),
            'permissions' => [
                'manage_users',
                'create_admins', // 確保默認具有創建管理員權限
                'view_reports'
            ],
            'last_login_at' => null,
            'email_verified_at' => null,
        ];
    }

    /**
     * 創建超級管理員狀態.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => [
                'super_admin',
                'manage_users',
                'manage_system',
                'create_admins',
                'delete_all_data',
                'view_all_data'
            ],
        ]);
    }

    /**
     * 創建基本管理員狀態.
     */
    public function basicAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => [
                'manage_users',
                'view_reports'
            ],
        ]);
    }

    /**
     * 創建沒有郵件的管理員狀態.
     */
    public function withoutEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => null,
        ]);
    }

    /**
     * 創建已驗證郵件的管理員狀態.
     */
    public function emailVerified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => now(),
        ]);
    }

    /**
     * 創建特定權限的管理員狀態.
     */
    public function withPermissions(array $permissions): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => $permissions,
        ]);
    }
}
