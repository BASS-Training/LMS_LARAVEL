<?php

namespace Tests\Feature;

use App\Http\Middleware\LogActivity;
use App\Jobs\LogActivityJob;
use App\Models\User;
use App\Services\SensitiveDataEncryptionService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ProfileContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sensitive-data.key' => 'base64:'.base64_encode(random_bytes(32))]);
    }

    private function profile(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'date_of_birth' => '2000-01-01',
            'gender' => 'male',
            'institution_name' => 'BASS Academy',
            'occupation' => 'Karyawan Swasta',
        ];
    }

    public function test_web_contact_fields_are_encrypted_displayed_preserved_and_clearable(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->patch('/profile', $this->profile($user) + [
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 10',
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $stored = DB::table('users')->where('id', $user->id)->value('phone');
        $this->assertStringStartsWith('v1:', $stored);
        $this->assertStringNotContainsString('081234567890', $stored);
        $this->assertSame('081234567890', $user->refresh()->phone);
        $this->assertArrayNotHasKey('phone', $user->toArray());
        $this->get('/profile')->assertOk()->assertSee('081234567890')->assertSee('Jl. Merdeka No. 10');

        $this->patch('/profile', $this->profile($user))->assertSessionHasNoErrors();
        $this->assertSame($stored, $user->refresh()->getRawOriginal('phone'));
        $this->assertSame('Jl. Merdeka No. 10', $user->address);

        $this->patch('/profile', $this->profile($user) + ['phone' => '', 'address' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($user->refresh()->phone);
        $this->assertNull($user->address);
        $this->assertNull($user->getRawOriginal('phone'));
    }

    public function test_mobile_contact_fields_are_encrypted_returned_preserved_and_clearable(): void
    {
        $user = User::factory()->create(['api_token' => 'contact-api-token']);
        $this->withToken('contact-api-token')->postJson('/api/mobile/profile', $this->profile($user) + [
            'phone' => '+6281234567890',
            'address' => 'Jakarta',
        ])->assertOk()->assertJsonPath('data.user.phone', '+6281234567890')
            ->assertJsonPath('data.user.address', 'Jakarta');

        $this->assertNotSame('+6281234567890', $user->refresh()->getRawOriginal('phone'));
        $this->postJson('/api/mobile/profile', $this->profile($user))
            ->assertOk()->assertJsonPath('data.user.phone', '+6281234567890')
            ->assertJsonPath('data.user.address', 'Jakarta');
        $this->postJson('/api/mobile/profile', $this->profile($user) + ['phone' => null, 'address' => null])
            ->assertOk()->assertJsonPath('data.user.phone', null)->assertJsonPath('data.user.address', null);
        $this->assertNull($user->refresh()->getRawOriginal('phone'));
    }

    public function test_invalid_contacts_are_rejected_on_web_and_mobile(): void
    {
        $user = User::factory()->create(['api_token' => 'invalid-contact-token']);
        $data = $this->profile($user) + ['phone' => 'not-a-phone', 'address' => str_repeat('a', 2001)];
        $this->actingAs($user)->patch('/profile', $data)
            ->assertSessionHasErrors(['phone', 'address'])
            ->assertSessionMissing('_old_input.phone');
        $this->withToken('invalid-contact-token')->postJson('/api/mobile/profile', $data)
            ->assertUnprocessable()->assertJsonValidationErrors(['phone', 'address']);
        $this->assertNull($user->refresh()->getRawOriginal('phone'));
    }

    public function test_encryption_is_randomized_and_supports_previous_keys(): void
    {
        $service = app(SensitiveDataEncryptionService::class);
        $first = $service->encrypt('081234567890');
        $this->assertNotSame($first, $service->encrypt('081234567890'));
        $oldKey = config('sensitive-data.key');
        config([
            'sensitive-data.key' => 'base64:'.base64_encode(random_bytes(32)),
            'sensitive-data.previous_keys' => [$oldKey],
        ]);
        $this->assertSame('081234567890', $service->decrypt($first));
        $this->assertSame('081234567890', $service->decrypt($service->encrypt('081234567890')));
    }

    public function test_modified_ciphertext_is_rejected(): void
    {
        $service = app(SensitiveDataEncryptionService::class);
        $payload = json_decode(base64_decode(substr($service->encrypt('081234567890'), 3)), true);
        $ciphertext = base64_decode($payload['value']);
        $ciphertext[0] = chr(ord($ciphertext[0]) ^ 1);
        $payload['value'] = base64_encode($ciphertext);
        $this->expectException(DecryptException::class);
        $service->decrypt('v1:'.base64_encode(json_encode($payload)));
    }

    public function test_missing_key_does_not_allow_plaintext_storage(): void
    {
        config(['sensitive-data.key' => null]);
        $this->expectException(RuntimeException::class);
        app(SensitiveDataEncryptionService::class)->encrypt('081234567890');
    }

    public function test_activity_job_excludes_phone_from_input_and_model_snapshots(): void
    {
        Bus::fake();
        $user = User::factory()->create(['phone' => '081234567890']);
        $this->actingAs($user);
        $request = Request::create('/test-contact/'.$user->id, 'PATCH', ['phone' => '+6281234567890']);
        $route = new Route('PATCH', '/test-contact/{user}', fn () => null);
        $route->bind($request);
        $route->setParameter('user', $user);
        $request->setRouteResolver(fn () => $route);

        (new LogActivity)->handle($request, function () use ($user) {
            $user->update(['phone' => '+6281234567890']);

            return response('OK');
        });

        Bus::assertDispatched(LogActivityJob::class, function ($job) {
            $serialized = serialize($job);
            $this->assertStringNotContainsString('081234567890', $serialized);
            $this->assertStringNotContainsString('+6281234567890', $serialized);
            $this->assertStringNotContainsString('v1:', $serialized);
            $this->assertStringNotContainsString('"phone"', $serialized);

            return true;
        });
    }
}
