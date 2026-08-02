@php
    $post = $getRecord();
    $mediaItems = $post->getMedia('*');
@endphp

@if ($mediaItems->isEmpty())
    <span class="text-gray-400 dark:text-gray-500">-</span>
@else
    <div class="flex flex-wrap gap-1">
        @foreach ($mediaItems as $media)
            @if (str_starts_with($media->mime_type, 'image'))
                <img
                    src="{{ $media->getUrl() }}"
                    alt="{{ $media->name }}"
                    class="h-16 w-16 rounded object-cover shadow"
                >
            @elseif (str_starts_with($media->mime_type, 'video'))
                <video
                    src="{{ $media->getUrl() }}"
                    class="h-16 w-28 rounded bg-black shadow"
                    controls
                    muted
                    preload="metadata"
                ></video>
            @elseif (str_starts_with($media->mime_type, 'audio'))
                <audio
                    src="{{ $media->getUrl() }}"
                    controls
                    preload="metadata"
                    class="h-8 w-44"
                ></audio>
            @else
                <a
                    href="{{ $media->getUrl() }}"
                    target="_blank"
                    class="text-primary-600 underline"
                >
                    Buka file
                </a>
            @endif
        @endforeach
    </div>
@endif
