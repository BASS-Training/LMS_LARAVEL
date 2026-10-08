@props(['id', 'name', 'value' => '', 'preset' => 'default', 'placeholder' => 'Tuliskan konten Anda di sini...'])

@php
    $toolbar = $preset === 'bio'
        ? [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link']],
        ]
        : ($preset === 'participant'
            ? [
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
            ]
        : [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']],
        ]);
@endphp

<div wire:ignore>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        {{ $attributes->merge(['class' => 'summernote']) }}
    >{{ $value }}</textarea>
</div>

@push('styles')
<style>
    body > .note-modal-backdrop {
        position: fixed !important;
        z-index: 1090 !important;
    }

    body > .note-modal {
        position: fixed !important;
        z-index: 1100 !important;
        overflow-y: auto;
    }

    body > .note-modal .note-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        min-height: 64px;
        height: auto;
        padding: 12px 20px;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        const editor = $('#{{ $id }}');

        function syncEditor(contents) {
            editor.val(contents);
            editor[0].dispatchEvent(new Event('input', { bubbles: true }));
        }

        editor.summernote({
            placeholder: @json($placeholder),
            tabsize: 2,
            dialogsInBody: true,
            height: {{ $preset === 'bio' ? 220 : 300 }},
            toolbar: @json($toolbar),
            dialogsInBody: true,
            callbacks: {
                onChange: syncEditor,
                onChangeCodeview: function() {
                    syncEditor(editor.summernote('code'));
                },
                @if ($preset === 'default')
                onImageUpload: function(files) {
                    Array.from(files).forEach(uploadImage);
                }
                @endif
            },
        });

        @if ($preset === 'default')
        function uploadImage(file) {
            let data = new FormData();
            data.append("image", file);
            data.append("_token", "{{ csrf_token() }}");

            $.ajax({
                url: "{{ route('images.upload') }}",
                method: "POST",
                data: data,
                contentType: false,
                processData: false,
                success: function(response) {
                    editor.summernote('insertImage', response.url);
                },
                error: function(response) {
                    const message = response.responseJSON?.errors?.image?.[0]
                        || response.responseJSON?.message
                        || 'Foto gagal diunggah. Periksa ukuran dan format file, lalu coba lagi.';
                    alert(message);
                }
            });
        }
        @endif
    });
</script>
@endpush
