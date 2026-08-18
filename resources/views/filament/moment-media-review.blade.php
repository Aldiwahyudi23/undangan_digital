<div class="space-y-4">
    <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400">Nama Tamu</dt>
            <dd class="text-sm font-medium">{{ $moment->guest_name }}</dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400">Tipe</dt>
            <dd class="text-sm font-medium">{{ $moment->type === 'post' ? 'Post (Foto/Video)' : 'Voice Note' }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-xs text-gray-500 dark:text-gray-400">Caption</dt>
            <dd class="text-sm">{{ $moment->caption ?: '-' }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-xs text-gray-500 dark:text-gray-400">Dibuat</dt>
            <dd class="text-sm">{{ $moment->created_at?->format('d M Y H:i') }}</dd>
        </div>
    </dl>

    <div>
        <h4 class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
            Media ({{ $moment->getMedia('*')->count() }})
        </h4>
        <div class="grid grid-cols-2 gap-3">
            @forelse ($moment->getMedia('*') as $media)
                @if (str_starts_with($media->mime_type, 'image'))
                    <a href="{{ $media->getUrl() }}" target="_blank" class="block">
                        <img
                            src="{{ $media->getUrl() }}"
                            alt="{{ $media->name }}"
                            class="w-full rounded-lg object-cover shadow"
                        >
                    </a>
                @elseif (str_starts_with($media->mime_type, 'video'))
                    <video
                        src="{{ $media->getUrl() }}"
                        controls
                        preload="metadata"
                        class="w-full rounded-lg bg-black shadow"
                    ></video>
                @elseif (str_starts_with($media->mime_type, 'audio'))
                    <audio
                        src="{{ $media->getUrl() }}"
                        controls
                        preload="metadata"
                        class="w-full"
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
            @empty
                <p class="col-span-2 text-sm text-gray-400 dark:text-gray-500">Tidak ada media.</p>
            @endforelse
        </div>
    </div>
</div>
