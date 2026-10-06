<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_response_is_unchanged_below_the_limit_and_returns_json_when_limited(): void
    {
        $payload = [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/mobile/auth/login', $payload)
                ->assertUnprocessable()
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Email atau password salah.',
                ]);
        }

        $this->postJson('/api/mobile/auth/login', $payload)
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Terlalu banyak permintaan. Silakan coba lagi.')
            ->assertJsonStructure(['retry_after']);
    }

    public function test_authenticated_api_limit_is_scoped_per_user(): void
    {
        $firstUser = User::factory()->create(['api_token' => 'first-rate-limit-token']);
        $secondUser = User::factory()->create(['api_token' => 'second-rate-limit-token']);

        for ($attempt = 1; $attempt <= 120; $attempt++) {
            $this->withToken($firstUser->api_token)
                ->getJson('/api/mobile/auth/me')
                ->assertOk();
        }

        $this->withToken($firstUser->api_token)
            ->getJson('/api/mobile/auth/me')
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonPath('status', 'error');

        $this->withToken($secondUser->api_token)
            ->getJson('/api/mobile/auth/me')
            ->assertOk();
    }
}
