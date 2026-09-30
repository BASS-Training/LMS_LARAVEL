<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileWebSessionTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'web-session-test-token';

    private User $user;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'api_token' => self::TOKEN,
            'avpn_verification_status' => 'not_required',
        ]);

        $this->course = Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'regular',
        ]);
    }

    /** Minta tautan handoff dan kembalikan payload `data`. */
    private function issue(?int $courseId = null): array
    {
        return $this->withToken(self::TOKEN)
            ->postJson('/api/mobile/web-session', [
                'course_id' => $courseId ?? $this->course->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->json('data');
    }

    private function cacheKeyFor(string $url): string
    {
        return 'web_handoff:'.hash('sha256', basename($url));
    }

    public function test_web_session_requires_mobile_authentication(): void
    {
        $this->postJson('/api/mobile/web-session', ['course_id' => $this->course->id])
            ->assertUnauthorized();
    }

    public function test_handoff_logs_the_mobile_user_into_a_web_session(): void
    {
        $url = $this->issue()['url'];

        $this->assertStringContainsString('/auth/handoff/', $url);
        // Token mentah tidak pernah disimpan — hanya hash-nya.
        $this->assertNotNull(Cache::get($this->cacheKeyFor($url)));

        $this->get($url)->assertRedirect(route('shop.show', $this->course, false));

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_handoff_token_is_single_use(): void
    {
        $url = $this->issue()['url'];

        $this->get($url);
        $this->assertAuthenticatedAs($this->user);
        $this->assertNull(Cache::get($this->cacheKeyFor($url)));

        // Simulasikan tab/browser lain yang belum punya session login.
        $this->flushSession();
        $this->app['auth']->forgetGuards();

        $this->get($url)->assertRedirect(route('shop.index'));
        $this->assertGuest();
    }

    public function test_expired_handoff_token_is_rejected(): void
    {
        $url = $this->issue()['url'];

        $this->travel(121)->seconds();

        $this->get($url)->assertRedirect(route('shop.index'));
        $this->assertGuest();
    }

    public function test_invalid_token_is_rejected_without_error_page(): void
    {
        $this->get('/auth/handoff/'.str_repeat('a', 64))
            ->assertRedirect(route('shop.index'));
    }

    public function test_unknown_and_non_catalog_courses_are_rejected(): void
    {
        $this->withToken(self::TOKEN)
            ->postJson('/api/mobile/web-session', ['course_id' => 999999])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $hidden = Course::factory()->create(['status' => 'draft', 'visibility' => 'private']);

        $this->withToken(self::TOKEN)
            ->postJson('/api/mobile/web-session', ['course_id' => $hidden->id])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_missing_course_id_is_rejected(): void
    {
        $this->withToken(self::TOKEN)
            ->postJson('/api/mobile/web-session', [])
            ->assertStatus(422);
    }

    public function test_handoff_replaces_an_existing_web_session(): void
    {
        $other = User::factory()->create(['api_token' => 'other-user-token']);

        // Browser sudah login sebagai user lain.
        $this->actingAs($other);

        $url = $this->issue()['url'];

        $this->get($url)->assertRedirect(route('shop.show', $this->course, false));

        $this->app['auth']->forgetGuards();
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_handoff_issuance_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->withToken(self::TOKEN)
                ->postJson('/api/mobile/web-session', ['course_id' => $this->course->id])
                ->assertOk();
        }

        $this->withToken(self::TOKEN)
            ->postJson('/api/mobile/web-session', ['course_id' => $this->course->id])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonPath('status', 'error');
    }

    public function test_unverified_user_returns_to_the_catalog_detail_after_otp(): void
    {
        $newUser = User::factory()->create([
            'api_token' => 'unverified-user-token',
            'email_verified_at' => null,
            'email_verification_optional' => false,
            'avpn_verification_status' => 'not_required',
        ]);

        $url = $this->withToken($newUser->api_token)
            ->postJson('/api/mobile/web-session', ['course_id' => $this->course->id])
            ->assertOk()
            ->json('data.url');

        $this->get($url)->assertRedirect(route('shop.show', $this->course, false));
        $this->assertAuthenticatedAs($newUser);

        // Gate verifikasi email menahan pengguna ke halaman OTP.
        $this->get(route('shop.show', $this->course))
            ->assertRedirect(route('verification.otp'));

        EmailOtp::create([
            'email' => $newUser->email,
            'purpose' => EmailOtp::PURPOSE_EMAIL_VERIFICATION,
            'code' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Verifikasi sungguhan → intended() mengembalikannya ke detail katalog,
        // bukan dashboard.
        $this->post('/verify-otp', ['code' => '123456'])
            ->assertRedirect(route('shop.show', $this->course, false));

        $this->app['auth']->forgetGuards();
        $this->assertAuthenticatedAs($newUser);
    }
}
