@props(['id', 'name', 'value' => '', 'preset' => 'default', 'placeholder' => 'Tuliskan konten Anda di sini...'])

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
        $('#{{ $id }}').summernote({
            placeholder: @json($placeholder),
            tabsize: 2,
            height: {{ $preset === 'bio' ? 220 : 300 }},
            toolbar: @json($preset === 'bio' ? [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
            ] : [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]),
            @if ($preset !== 'bio')
            callbacks: {
                onImageUpload: function(files) {
                    uploadImage(files[0], '#{{ $id }}');
                }
            },
            @endif
        });

        @if ($preset !== 'bio')
        function uploadImage(file, editor) {
            let data = new FormData();
            data.append("image", file);
            data.append("_token", "{{ csrf_token() }}"); // Tambahkan CSRF token

            $.ajax({
                url: "{{ route('images.upload') }}",
                method: "POST",
                data: data,
                contentType: false,
                processData: false,
                success: function(response) {
                    $(editor).summernote('insertImage', response.url);
                },
                error: function(data) {
                    console.error(data);
                }
            });
        }
        @endif
    });
</script>
@endpush
