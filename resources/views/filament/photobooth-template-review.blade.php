@php
    $slots = $template->slots();
    $frame = $template->frame_image;
    if (is_array($frame)) {
        $frame = array_values($frame)[0] ?? null;
    }
    if ($frame && ! str_starts_with($frame, 'http')) {
        $frame = asset('storage/' . ltrim($frame, '/'));
    }
    [$frameWidth, $frameHeight] = array_pad($template->frameDimensions() ?? [null, null], 2, null);
@endphp

<div class="space-y-4">
    <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400">Judul</dt>
            <dd class="text-sm font-medium">{{ $template->title }}</dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400">Slug</dt>
            <dd class="text-sm">{{ $template->slug }}</dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400">Status</dt>
            <dd class="text-sm">{{ $template->is_active ? 'Aktif' : 'Non-aktif' }}</dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400">Jumlah Slot</dt>
            <dd class="text-sm">{{ count($slots) }}</dd>
        </div>
    </dl>

    <div>
        <h4 class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Preview Frame &amp; Posisi Slot</h4>
        @include('filament.partials.photobooth-slot-preview', [
            'slots' => $slots,
            'frame' => $frame,
            'frameWidth' => $frameWidth,
            'frameHeight' => $frameHeight,
        ])
    </div>

    <div>
        <h4 class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Daftar Slot</h4>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800 text-left text-xs uppercase text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">Index</th>
                        <th class="px-3 py-2">Bentuk</th>
                        <th class="px-3 py-2">X</th>
                        <th class="px-3 py-2">Y</th>
                        <th class="px-3 py-2">Lebar</th>
                        <th class="px-3 py-2">Tinggi</th>
                        <th class="px-3 py-2">Rotasi</th>
                        <th class="px-3 py-2">Radius</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($slots as $slot)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="px-3 py-2 font-medium">{{ $slot['index'] ?? '-' }}</td>
                            <td class="px-3 py-2">{{ ($slot['shape'] ?? '') === 'circle' ? 'Circle' : 'Rect' }}</td>
                            <td class="px-3 py-2">{{ $slot['x'] ?? 0 }}</td>
                            <td class="px-3 py-2">{{ $slot['y'] ?? 0 }}</td>
                            <td class="px-3 py-2">{{ $slot['width'] ?? 0 }}</td>
                            <td class="px-3 py-2">{{ $slot['height'] ?? 0 }}</td>
                            <td class="px-3 py-2">{{ $slot['rotation'] ?? 0 }}</td>
                            <td class="px-3 py-2">{{ $slot['radius'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td colspan="8" class="px-3 py-3 text-gray-400">Belum ada slot.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
