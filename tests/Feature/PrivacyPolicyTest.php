<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_read_the_indonesian_policy_and_switch_to_english(): void
    {
        $this->get(route('privacy-policy'))
            ->assertOk()
            ->assertSee('<html lang="id"', false)
            ->assertSee('Kebijakan Privasi')
            ->assertSee('9. Penghapusan akun')
            ->assertSee('admin@basstrainingacademy.com')
            ->assertSee(route('privacy-policy', ['lang' => 'en']), false);
    }

    public function test_guests_can_open_the_english_policy_directly(): void
    {
        $this->get(route('privacy-policy', ['lang' => 'en']))
            ->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('<h1 class="mt-2 text-3xl font-extrabold sm:text-4xl">Privacy Policy</h1>', false)
            ->assertSee('9. Account deletion')
            ->assertSee('Account Deletion Request')
            ->assertSee(route('privacy-policy', ['lang' => 'id']), false);
    }

    public function test_unsupported_and_array_language_values_fall_back_to_indonesian(): void
    {
        foreach (['fr', ['en']] as $language) {
            $this->get(route('privacy-policy', ['lang' => $language]))
                ->assertOk()
                ->assertSee('<html lang="id"', false)
                ->assertSee('9. Penghapusan akun');
        }
    }

    public function test_registration_and_public_footer_link_to_the_policy(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('privacy-policy'), false);

        $this->get(route('privacy-policy'))
            ->assertOk()
            ->assertSee('Kebijakan Privasi / Privacy Policy')
            ->assertSee('href="'.route('privacy-policy').'"', false);
    }
}
