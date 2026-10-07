<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CertificateTemplateController extends Controller
{
    public function index()
    {
        $templates = CertificateTemplate::latest()->paginate(10);

        return view('admin.certificate-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.certificate-templates.create');
    }

    public function createEnhanced()
    {
        return view('admin.certificate-templates.enhanced-create');
    }

    public function createAdvanced()
    {
        return view('admin.certificate-templates.advanced-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'layout_data' => 'required|json',
            'backgrounds' => 'nullable|array',
            'backgrounds.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $newImagePaths = [];

        try {
            $layoutData = json_decode($request->layout_data, true, 512, JSON_THROW_ON_ERROR);
            $backgroundFiles = $request->file('backgrounds', []);
            [$layoutData, $newImagePaths] = $this->prepareLayoutBackgrounds($layoutData, $backgroundFiles);

            $template = DB::transaction(fn () => CertificateTemplate::create([
                'name' => $request->name,
                'layout_data' => $layoutData,
            ]));

            // Handle AJAX requests from advanced editor
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Template created successfully.',
                    'template' => $template,
                    'redirect_url' => route('admin.certificate-templates.index'),
                ]);
            }

            return redirect()->route('admin.certificate-templates.index')
                ->with('success', 'Template created successfully.');

        } catch (ValidationException $e) {
            $this->deleteImages($newImagePaths);

            throw $e;
        } catch (\Exception $e) {
            $this->deleteImages($newImagePaths);
            Log::error('Error creating certificate template: '.$e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create template. Please try again.',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['error' => 'Failed to create template. Please try again.'])->withInput();
        }
    }

    public function show(CertificateTemplate $certificateTemplate)
    {
        return view('admin.certificate-templates.preview', compact('certificateTemplate'));
    }

    public function edit(CertificateTemplate $certificateTemplate)
    {
        return view('admin.certificate-templates.edit', compact('certificateTemplate'));
    }

    public function editEnhanced(CertificateTemplate $certificateTemplate)
    {
        if ($certificateTemplate->editorType() === 'advanced') {
            return redirect()->route('admin.certificate-templates.edit-advanced', $certificateTemplate)
                ->with('warning', 'Template ini menggunakan Advanced Editor.');
        }

        return view('admin.certificate-templates.enhanced-edit', compact('certificateTemplate'));
    }

    public function editAdvanced(CertificateTemplate $certificateTemplate)
    {
        return view('admin.certificate-templates.advanced-edit', compact('certificateTemplate'));
    }

    public function update(Request $request, CertificateTemplate $certificateTemplate)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'layout_data' => 'required|json',
            'backgrounds' => 'nullable|array',
            'backgrounds.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Increased to 5MB
        ]);

        $uploadedImagePaths = [];

        try {
            $newLayoutData = json_decode($request->layout_data, true, 512, JSON_THROW_ON_ERROR);
            $oldLayoutData = $certificateTemplate->layout_data ?? [];
            $backgroundFiles = $request->file('backgrounds', []);
            $oldImagePaths = collect($oldLayoutData)->pluck('background_image_path')->filter()->values();
            [$newLayoutData, $uploadedImagePaths] = $this->prepareLayoutBackgrounds($newLayoutData, $backgroundFiles, $oldLayoutData);
            $newImagePaths = collect($newLayoutData)->pluck('background_image_path')->filter();

            DB::transaction(function () use ($certificateTemplate, $request, $newLayoutData) {
                $certificateTemplate->update([
                    'name' => $request->name,
                    'layout_data' => $newLayoutData,
                ]);
            });

            // File lama baru aman dihapus setelah perubahan database berhasil.
            $this->deleteImages($oldImagePaths->diff($newImagePaths)->all());

            // Handle AJAX requests from advanced editor
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Template updated successfully.',
                    'template' => $certificateTemplate,
                ]);
            }

            return redirect()->route('admin.certificate-templates.index')
                ->with('success', 'Template updated successfully.');

        } catch (ValidationException $e) {
            $this->deleteImages($uploadedImagePaths);

            throw $e;
        } catch (\Exception $e) {
            $this->deleteImages($uploadedImagePaths);
            Log::error('Error updating certificate template: '.$e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update template. Please try again.',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['error' => 'Failed to update template. Please try again.'])->withInput();
        }
    }

    public function destroy(CertificateTemplate $certificateTemplate)
    {
        try {
            // Hapus semua gambar latar yang terkait
            foreach ($certificateTemplate->layout_data as $page) {
                if (isset($page['background_image_path']) && Storage::disk('public')->exists($page['background_image_path'])) {
                    Storage::disk('public')->delete($page['background_image_path']);
                }
            }

            $certificateTemplate->delete();

            return redirect()->route('admin.certificate-templates.index')
                ->with('success', 'Template deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Error deleting certificate template: '.$e->getMessage());

            return back()->withErrors(['error' => 'Failed to delete template. Please try again.']);
        }
    }

    public function preview(CertificateTemplate $certificateTemplate)
    {
        return view('admin.certificate-templates.preview', compact('certificateTemplate'));
    }

    public function generatePreview(Request $request, CertificateTemplate $certificateTemplate)
    {
        $sampleData = [
            'name' => $request->get('name', 'John Doe'),
            'course_title' => $request->get('course_title', 'Sample Course Title'),
            'completion_date' => $request->get('completion_date', now()->format('F d, Y')),
            'instructor_name' => $request->get('instructor_name', 'Dr. Jane Smith'),
            'organization' => $request->get('organization', 'Learning Organization'),
            'grade' => $request->get('grade', 'A+'),
        ];

        return response()->json([
            'success' => true,
            'preview_data' => $sampleData,
            'template' => $certificateTemplate,
        ]);
    }

    public function duplicate(CertificateTemplate $certificateTemplate)
    {
        try {
            // Copy layout data
            $layoutData = $certificateTemplate->layout_data;
            $newBackgroundFiles = [];

            // Process each page and duplicate background images
            foreach ($layoutData as $index => &$page) {
                if (isset($page['background_image_path']) && Storage::disk('public')->exists($page['background_image_path'])) {
                    // Generate new filename
                    $originalPath = $page['background_image_path'];
                    $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
                    $newFileName = 'background_'.time().'_'.$index.'_copy.'.$extension;
                    $newPath = 'certificate_backgrounds/'.$newFileName;

                    // Copy the file
                    Storage::disk('public')->copy($originalPath, $newPath);
                    $page['background_image_path'] = $newPath;
                }
            }

            // Create new template
            $duplicatedTemplate = CertificateTemplate::create([
                'name' => $certificateTemplate->name.' (Copy)',
                'layout_data' => $layoutData,
            ]);

            return redirect()->route('admin.certificate-templates.index')
                ->with('success', 'Template duplicated successfully as "'.$duplicatedTemplate->name.'".');

        } catch (\Exception $e) {
            Log::error('Error duplicating certificate template: '.$e->getMessage());

            return back()->withErrors(['error' => 'Failed to duplicate template. Please try again.']);
        }
    }

    /**
     * @return array{0: array, 1: array<int, string>}
     */
    private function prepareLayoutBackgrounds(array $layoutData, array $backgroundFiles, array $oldLayoutData = []): array
    {
        if ($layoutData === []) {
            throw ValidationException::withMessages(['layout_data' => 'Layout harus memiliki setidaknya satu halaman.']);
        }

        $newImagePaths = [];
        $oldImagePaths = collect($oldLayoutData)->pluck('background_image_path')->filter()->values();
        $oldPagesById = collect($oldLayoutData)
            ->filter(fn ($page) => is_array($page) && ! empty($page['id']))
            ->keyBy(fn ($page) => (string) $page['id']);

        try {
            foreach ($layoutData as $index => &$page) {
                if (! is_array($page)) {
                    throw ValidationException::withMessages(['layout_data' => 'Data halaman template tidak valid.']);
                }

                if (isset($page['backgroundImage']) && str_starts_with($page['backgroundImage'], 'data:image/')) {
                    $path = $this->storeBase64Background($page['backgroundImage']);
                    $newImagePaths[] = $path;
                    $page['background_image_path'] = $path;
                    unset($page['backgroundImage']);
                } elseif (isset($backgroundFiles[$index]) && $backgroundFiles[$index]) {
                    $path = $backgroundFiles[$index]->store('certificate_backgrounds', 'public');
                    $newImagePaths[] = $path;
                    $page['background_image_path'] = $path;
                } elseif (array_key_exists('background_image_path', $page)) {
                    $path = $page['background_image_path'];
                    if ($path !== null && (! is_string($path) || ! $oldImagePaths->contains($path))) {
                        throw ValidationException::withMessages([
                            'layout_data' => 'Path latar belakang tidak berasal dari template ini.',
                        ]);
                    }
                } else {
                    $oldPage = ! empty($page['id']) ? $oldPagesById->get((string) $page['id']) : null;
                    $fallbackPath = $oldPage['background_image_path'] ?? ($oldLayoutData[$index]['background_image_path'] ?? null);
                    if ($fallbackPath) {
                        $page['background_image_path'] = $fallbackPath;
                    }
                }

                if (empty($page['background_image_path']) && ! array_key_exists('backgroundColor', $page)) {
                    $page['backgroundColor'] = '#ffffff';
                }
            }
            unset($page);
        } catch (\Throwable $exception) {
            $this->deleteImages($newImagePaths);

            throw $exception;
        }

        return [$layoutData, $newImagePaths];
    }

    private function storeBase64Background(string $dataUrl): string
    {
        if (! preg_match('/^data:image\/(jpeg|png|gif|webp);base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
            throw ValidationException::withMessages(['layout_data' => 'Format gambar base64 tidak valid.']);
        }

        $imageData = base64_decode($matches[2], true);
        if ($imageData === false || strlen($imageData) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['layout_data' => 'Ukuran gambar base64 maksimal 5 MB.']);
        }

        $imageInfo = @getimagesizefromstring($imageData);
        $mimeToExtension = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $extension = $mimeToExtension[$imageInfo['mime'] ?? ''] ?? null;
        if (! $extension) {
            throw ValidationException::withMessages(['layout_data' => 'Isi gambar base64 tidak valid.']);
        }

        $path = 'certificate_backgrounds/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($path, $imageData);

        return $path;
    }

    private function deleteImages(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
