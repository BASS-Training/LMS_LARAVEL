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

@push('scripts')
<script>
    $(document).ready(function() {
        const editor = $('#{{ $id }}');
        const visibilityContainer = editor.closest('.content-field, [x-show]').get(0);

        function syncEditor(contents) {
            editor.val(contents);
            editor[0].dispatchEvent(new Event('input', { bubbles: true }));
        }

        function initializeEditor() {
            if (editor.data('summernote') || !editor.is(':visible')) {
                return false;
            }

            editor.summernote({
                placeholder: @json($placeholder),
                tabsize: 2,
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

            return true;
        }

        editor.on('rich-editor:init', initializeEditor);

        if (!initializeEditor() && visibilityContainer) {
            const observer = new MutationObserver(function() {
                if (initializeEditor()) {
                    observer.disconnect();
                }
            });

            observer.observe(visibilityContainer, {
                attributes: true,
                attributeFilter: ['class', 'style'],
            });
        }

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
