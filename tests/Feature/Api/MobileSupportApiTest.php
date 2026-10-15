<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileSupportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_is_public_and_uses_the_web_translations(): void
    {
        foreach (['id', 'en'] as $language) {
            $this->getJson('/api/mobile/privacy-policy?lang='.$language)
                ->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('data.language', $language)
                ->assertJsonPath('data.policy', trans('privacy', [], $language));
        }

        $this->getJson('/api/mobile/privacy-policy')->assertJsonPath('data.language', 'id');
        $this->getJson('/api/mobile/privacy-policy?lang=fr')->assertJsonPath('data.language', 'id');
    }

    public function test_customer_service_is_public_and_matches_the_web_page(): void
    {
        $data = $this->getJson('/api/mobile/customer-service')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.contacts.0.value', '+62 821-1279-8728')
            ->assertJsonPath('data.contacts.1.value', 'admin@basstrainingacademy.com')
            ->assertJsonPath('data.service_hours.timezone', 'Asia/Jakarta')
            ->assertJsonCount(6, 'data.faq')
            ->json('data');

        $page = $this->get('/customer-service')->assertOk();
        foreach ($data['contacts'] as $contact) {
            $page->assertSee($contact['value'])->assertSee($contact['url']);
        }
        foreach ($data['faq'] as $faq) {
            $page->assertSee($faq['question'])->assertSee($faq['answer']);
        }
    }
}
