<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens; /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use MustVerifyEmail;
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'permissions',
        'last_login_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'is_active',
    ];

    /**
     * Get the is_active attribute.
     * A user is active if they are not soft deleted.
     */
    public function getIsActiveAttribute(): bool
    {
        return null === $this->deleted_at;
    }

    /**
     * 檢查用戶是否為管理員
     */
    public function isAdmin(): bool
    {
        return \in_array($this->role, ['admin', 'super_admin']);
    }

    /**
     * 檢查管理員是否具有指定權限.
     *
     * @param string $permission 權限名稱
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isAdmin()) {
            return false;
        }

        // 超級管理員擁有所有權限
        if ('super_admin' === $this->role) {
            return true;
        }

        return \in_array($permission, $this->permissions ?? []);
    }

    /**
     * 檢查管理員是否具有任意一個指定權限.
     *
     * @param array $permissions 權限列表
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if (!$this->isAdmin()) {
            return false;
        }

        if ('super_admin' === $this->role) {
            return true;
        }

        return !empty(array_intersect($permissions, $this->permissions ?? []));
    }

    /**
     * 檢查管理員是否具有所有指定權限.
     *
     * @param array $permissions 權限列表
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if (!$this->isAdmin()) {
            return false;
        }

        if ('super_admin' === $this->role) {
            return true;
        }

        return empty(array_diff($permissions, $this->permissions ?? []));
    }

    /**
     * 添加權限.
     *
     * @param array|string $permissions 權限或權限列表
     */
    public function givePermission(string|array $permissions): void
    {
        if (!$this->isAdmin()) {
            return;
        }

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
        if (!$this->isAdmin()) {
            return;
        }

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
     * 檢查是否為超級管理員
     */
    public function isSuperAdmin(): bool
    {
        return 'super_admin' === $this->role;
    }

    /**
     * 檢查是否需要 email 驗證.
     */
    public function shouldVerifyEmail(): bool
    {
        $requireEmailVerification = config('auth.require_email_verification', true);

        // 如果環境變數設定不需要驗證，則不需要
        if (!$requireEmailVerification) {
            return false;
        }

        // 管理員可以不需要 email 驗證
        if ($this->isAdmin()) {
            return false;
        }

        return null === $this->email_verified_at;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }
}
