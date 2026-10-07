<?php

namespace Tests\Feature\Certificates;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EnhancedCertificateTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        foreach (['view certificate templates', 'create certificate templates', 'update certificate templates'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->admin->givePermissionTo([
            'view certificate templates',
            'create certificate templates',
            'update certificate templates',
        ]);
    }

    public function test_explicit_null_removes_an_existing_background(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('certificate_backgrounds/old.png', 'old-image');
        $template = CertificateTemplate::create([
            'name' => 'Template Lama',
            'layout_data' => [$this->page('page-one', 'certificate_backgrounds/old.png')],
        ]);

        $page = $this->page('page-one', null);

        $this->actingAs($this->admin)
            ->put(route('admin.certificate-templates.update', $template), [
                'name' => 'Template Baru',
                'layout_data' => json_encode([$page]),
            ])
            ->assertRedirect(route('admin.certificate-templates.index'));

        $this->assertNull($template->fresh()->layout_data[0]['background_image_path']);
        Storage::disk('public')->assertMissing('certificate_backgrounds/old.png');
    }

    public function test_missing_path_uses_page_id_when_pages_are_reordered(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('certificate_backgrounds/first.png', 'first');
        Storage::disk('public')->put('certificate_backgrounds/second.png', 'second');
        $template = CertificateTemplate::create([
            'name' => 'Template',
            'layout_data' => [
                $this->page('page-one', 'certificate_backgrounds/first.png'),
                $this->page('page-two', 'certificate_backgrounds/second.png'),
            ],
        ]);

        $secondPage = $this->page('page-two', null);
        $firstPage = $this->page('page-one', null);
        unset($secondPage['background_image_path'], $firstPage['background_image_path']);

        $this->actingAs($this->admin)
            ->put(route('admin.certificate-templates.update', $template), [
                'name' => 'Template',
                'layout_data' => json_encode([$secondPage, $firstPage]),
            ])
            ->assertRedirect();

        $layout = $template->fresh()->layout_data;
        $this->assertSame('certificate_backgrounds/second.png', $layout[0]['background_image_path']);
        $this->assertSame('certificate_backgrounds/first.png', $layout[1]['background_image_path']);
    }

    public function test_background_upload_uses_the_explicit_page_index(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('admin.certificate-templates.store'), [
                'name' => 'Dua Halaman',
                'layout_data' => json_encode([
                    $this->page('page-one', null),
                    $this->page('page-two', null),
                ]),
                'backgrounds' => [
                    1 => UploadedFile::fake()->image('second-page.png', 1123, 794),
                ],
            ])
            ->assertRedirect(route('admin.certificate-templates.index'));

        $layout = CertificateTemplate::where('name', 'Dua Halaman')->firstOrFail()->layout_data;
        $this->assertNull($layout[0]['background_image_path']);
        $this->assertNotNull($layout[1]['background_image_path']);
        Storage::disk('public')->assertExists($layout[1]['background_image_path']);
    }

    public function test_update_rejects_a_background_path_from_another_template(): void
    {
        Storage::fake('public');
        $template = CertificateTemplate::create([
            'name' => 'Template',
            'layout_data' => [$this->page('page-one', null)],
        ]);
        $page = $this->page('page-one', 'certificate_backgrounds/foreign.png');

        $this->actingAs($this->admin)
            ->from(route('admin.certificate-templates.edit-enhanced', $template))
            ->put(route('admin.certificate-templates.update', $template), [
                'name' => 'Template',
                'layout_data' => json_encode([$page]),
            ])
            ->assertRedirect(route('admin.certificate-templates.edit-enhanced', $template))
            ->assertSessionHasErrors('layout_data');

        $this->assertNull($template->fresh()->layout_data[0]['background_image_path']);
    }

    public function test_paper_orientation_uses_explicit_dimensions_and_legacy_landscape_defaults(): void
    {
        $portrait = CertificateTemplate::create([
            'name' => 'Portrait',
            'layout_data' => [['width' => 794, 'height' => 1123, 'elements' => []]],
        ]);
        $legacy = CertificateTemplate::create([
            'name' => 'Legacy',
            'layout_data' => [['elements' => []]],
        ]);

        $this->assertSame('portrait', $portrait->paperOrientation());
        $this->assertSame('landscape', $legacy->paperOrientation());
        $this->assertSame('advanced', $portrait->editorType());
        $this->assertSame('enhanced', $legacy->editorType());
    }

    public function test_google_certificate_fonts_are_available_in_editor_preview_and_pdf_renderer(): void
    {
        $template = CertificateTemplate::create([
            'name' => 'Google Font Template',
            'layout_data' => [[
                ...$this->page('page-one', null),
                'elements' => [[
                    'id' => 'title',
                    'content' => '@{{name}}',
                    'x' => 100,
                    'y' => 100,
                    'width' => 400,
                    'height' => 80,
                    'fontSize' => 36,
                    'fontFamily' => 'Great Vibes',
                ]],
            ]],
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.certificate-templates.edit-enhanced', $template))
            ->assertOk()
            ->assertSee('Great Vibes')
            ->assertSee('fonts.googleapis.com/css2', false);

        $course = Course::factory()->create();
        $certificate = Certificate::create([
            'user_id' => $this->admin->id,
            'course_id' => $course->id,
            'certificate_template_id' => $template->id,
            'certificate_code' => Certificate::generateCertificateCode(),
            'issued_at' => now(),
        ]);
        $rendered = view('certificates.template-render', compact('certificate'))->render();

        $this->assertStringContainsString('fonts.googleapis.com/css2', $rendered);
        $this->assertStringContainsString("font-family: 'Great Vibes'", $rendered);
        $this->assertSame(
            ['fonts.googleapis.com', 'fonts.gstatic.com'],
            config('certificate.pdf_remote_hosts'),
        );
    }

    public function test_enhanced_create_page_has_a_live_preview_action(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.certificate-templates.create-enhanced'))
            ->assertOk()
            ->assertSee('aria-label="Pratinjau template"', false)
            ->assertSee('@click="openPreview"', false)
            ->assertSee('enhancedCertificateFontStylesheetUrl', false);
    }

    private function page(string $id, ?string $backgroundPath): array
    {
        return [
            'id' => $id,
            'editorType' => 'enhanced',
            'schemaVersion' => 2,
            'width' => 1123,
            'height' => 794,
            'background_image_path' => $backgroundPath,
            'backgroundSize' => 'cover',
            'backgroundPosition' => 'center',
            'elements' => [],
        ];
    }
}
