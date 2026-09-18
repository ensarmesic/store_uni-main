<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login_with_username_and_password(): void
    {
        $this->post(route('account.register'), ['username' => 'testuser', 'password' => 'secret123', 'password_confirmation' => 'secret123'])->assertRedirect();
        $user = User::where('username', 'testuser')->firstOrFail();
        $this->assertNotSame('secret123', $user->password);

        $this->post(route('account.login'), ['username' => 'testuser', 'password' => 'secret123'])->assertRedirect(route('account'));
        $this->assertSame($user->id, session('user_id'));

        $this->post(route('account.logout'))->assertRedirect(route('account'));
        $this->assertNull(session('user_id'));
    }
}
