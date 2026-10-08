<?php

namespace Tests\Unit;

use App\Services\ParticipantRichTextSanitizer;
use PHPUnit\Framework\TestCase;

class ParticipantRichTextSanitizerTest extends TestCase
{
    public function test_it_keeps_supported_formatting_and_removes_active_content(): void
    {
        $sanitizer = new ParticipantRichTextSanitizer;

        $result = $sanitizer->sanitize(
            '<p><strong>Jawaban</strong><script>alert(1)</script>'.
            '<a href="javascript:alert(2)" onclick="alert(3)">tautan</a></p>'
        );

        $this->assertStringContainsString('<p><strong>Jawaban</strong>', $result);
        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringNotContainsString('onclick', $result);
    }

    public function test_it_allows_safe_lists_and_links(): void
    {
        $sanitizer = new ParticipantRichTextSanitizer;

        $result = $sanitizer->sanitize(
            '<ol><li>Satu</li><li>Dua</li></ol><a href="https://example.com">Referensi</a>'
        );

        $this->assertStringContainsString('<ol><li>Satu</li><li>Dua</li></ol>', $result);
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
    }
}
