<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * 系統管理員用戶模型.
 *
 * 用於系統管理員的認證和授權管理
 * 與一般用戶 (User) 分離，提供更高的安全性
 *
 * @property int                             $id
 * @property string                          $username      用戶名（唯一）
 * @property string                          $password      密碼
 * @property null|string                     $email         電子郵件（可選）
 * @property string                          $name          顯示名稱
 * @property null|array                      $permissions   權限列表
 * @property null|\Illuminate\Support\Carbon $last_login_at 最後登入時間
 * @property null|\Illuminate\Support\Carbon $created_at    創建時間
 * @property null|\Illuminate\Support\Carbon $updated_at    更新時間
 * @property null|\Illuminate\Support\Carbon $deleted_at    軟刪除時間
 */
class SysUser extends Authenticatable
{
    use HasApiTokens; /** @use HasFactory<\Database\Factories\SysUserFactory> */
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    /**
     * 資料表名稱.
     *
     * @var string
     */
    protected $table = 'sys_users';

    /**
     * 可批量賦值的屬性.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'email',
        'name',
        'permissions',
        'last_login_at',
    ];

    /**
     * 應該隱藏的屬性（用於序列化）.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 屬性類型轉換.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'array',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    /**
     * 檢查管理員是否具有指定權限.
     *
     * @param string $permission 權限名稱
     */
    public function hasPermission(string $permission): bool
    {
        return \in_array($permission, $this->permissions ?? []);
    }

    /**
     * 檢查管理員是否具有任意一個指定權限.
     *
     * @param array $permissions 權限列表
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return !empty(array_intersect($permissions, $this->permissions ?? []));
    }

    /**
     * 檢查管理員是否具有所有指定權限.
     *
     * @param array $permissions 權限列表
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return empty(array_diff($permissions, $this->permissions ?? []));
    }

    /**
     * 添加權限.
     *
     * @param array|string $permissions 權限或權限列表
     */
    public function givePermission(string|array $permissions): void
    {
        $permissions = \is_array($permissions) ? $permissions : [$permissions];
        $currentPermissions = $this->permissions ?? [];

        $this->permissions = array_unique(array_merge($currentPermissions, $permissions));
        $this->save();
    }

    /**
     * 移除權限.
     *
     * @param array|string $permissions 權限或權限列表
     */
    public function revokePermission(string|array $permissions): void
    {
        $permissions = \is_array($permissions) ? $permissions : [$permissions];
        $currentPermissions = $this->permissions ?? [];

        $this->permissions = array_diff($currentPermissions, $permissions);
        $this->save();
    }

    /**
     * 更新最後登入時間.
     */
    public function updateLastLogin(): void
    {
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * 檢查是否為超級管理員（擁有所有權限）.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasPermission('super_admin')
               || $this->hasAllPermissions(['manage_users', 'manage_system', 'create_admins']);
    }
}
