<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Debug Registration Events.
 *
 * @internal
 *
 * @coversNothing
 */
final class RegistrationEventDebugTest extends TestCase
{
    use RefreshDatabase;

    public function testRegistrationEventFiring(): void
    {
        Mail::fake();
        Notification::fake();

        // 監聽 Registered 事件
        Event::listen(Registered::class, function (Registered $event) {
            dump('Registered event fired for user: ' . $event->user->email);
        });

        // 註冊用戶
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'username' => 'test_user',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);

        // 檢查用戶是否建立
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // 檢查用戶驗證狀態
        dump('User email_verified_at: ' . ($user->email_verified_at ? $user->email_verified_at->toDateTimeString() : 'null'));
        dump('User hasVerifiedEmail(): ' . ($user->hasVerifiedEmail() ? 'true' : 'false'));
        dump('User instanceof MustVerifyEmail: ' . ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail ? 'true' : 'false'));

        // 檢查通知是否被發送
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }
}
