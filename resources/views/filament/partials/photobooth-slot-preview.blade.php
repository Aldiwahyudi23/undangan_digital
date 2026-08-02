@php
    $slots = $slots ?? [];
    $frame = $frame ?? null;
    $frameWidth = $frameWidth ?? null;
    $frameHeight = $frameHeight ?? null;
    $maxWidth = $maxWidth ?? 560;

    if (is_array($frame)) {
        $frame = array_values($frame)[0] ?? null;
    }

    if ($frame && ! str_starts_with($frame, 'http')) {
        $frame = asset('storage/' . ltrim($frame, '/'));
    }

    $w = (float) ($frameWidth ?: 0);
    $h = (float) ($frameHeight ?: 0);
    $scale = $w > 0 ? min(1, $maxWidth / $w) : 0;

    $hasFrame = $frame && $w > 0 && $h > 0;

    $overflowSlots = [];
    foreach ($slots as $slot) {
        $left = (float) ($slot['x'] ?? 0);
        $top = (float) ($slot['y'] ?? 0);
        $right = $left + (float) ($slot['width'] ?? 0);
        $bottom = $top + (float) ($slot['height'] ?? 0);

        $overflowSlots[] = $hasFrame && ($left < 0 || $top < 0 || $right > $w || $bottom > $h);
    }

    $hasOverflow = $hasFrame && in_array(true, $overflowSlots, true);
@endphp

<div class="space-y-3">
    @if ($hasOverflow)
        <div class="rounded-lg bg-danger-50 p-3 text-xs text-danger-700 dark:bg-danger-400/10 dark:text-danger-400">
            Ada slot yang berada di luar area frame. Periksa kembali koordinat X/Y atau ukuran slot.
        </div>
    @endif

    @if (! $frame)
        <div class="flex h-40 w-full items-center justify-center rounded-lg border border-dashed border-gray-400 text-sm text-gray-400 dark:text-gray-500">
            Upload gambar frame terlebih dahulu untuk melihat preview posisi slot.
        </div>
    @elseif (! $hasFrame)
        <div class="flex h-40 w-full items-center justify-center rounded-lg border border-dashed border-gray-400 text-sm text-gray-400 dark:text-gray-500">
            Gambar frame belum tersedia untuk diukur. Pastikan file sudah ter-upload &amp; simpan.
        </div>
    @else
        <div class="flex justify-center overflow-x-auto rounded-lg border border-gray-200 bg-gray-100 p-4 dark:border-white/10 dark:bg-gray-900">
            <div
                style="position: relative; flex: none; width: {{ $w * $scale }}px; height: {{ $h * $scale }}px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2);">
                <img
                    src="{{ $frame }}"
                    alt="Frame"
                    style="display: block; width: {{ $w * $scale }}px; height: {{ $h * $scale }}px; border-radius: 4px;">

                @foreach ($slots as $i => $slot)
                    @php
                        $sx = (float) ($slot['x'] ?? 0) * $scale;
                        $sy = (float) ($slot['y'] ?? 0) * $scale;
                        $sw = (float) ($slot['width'] ?? 0) * $scale;
                        $sh = (float) ($slot['height'] ?? 0) * $scale;
                        $isCircle = ($slot['shape'] ?? '') === 'circle';
                        $radius = $isCircle ? '50%' : ((float) ($slot['radius'] ?? 0) * $scale . 'px');
                        $rotation = (float) ($slot['rotation'] ?? 0);
                        $slotIndex = $slot['index'] ?? ($i + 1);
                    @endphp
                    <div
                        style="position: absolute; left: {{ $sx }}px; top: {{ $sy }}px; width: {{ $sw }}px; height: {{ $sh }}px; border: 2px solid #6366f1; background: rgba(99,102,241,.18); border-radius: {{ $radius }};{{ $rotation ? ' transform: rotate(' . $rotation . 'deg);' : '' }} display: flex; align-items: flex-start; justify-content: flex-start;"
                    >
                        <span style="margin: 2px 4px; font-size: 11px; font-weight: 700; color: #4338ca;">#{{ $slotIndex }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-600 dark:text-gray-300">
        <div>Frame: <span class="font-semibold">{{ $hasFrame ? round($w) . ' × ' . round($h) . ' px' : '—' }}</span></div>
        <div>Jumlah Slot: <span class="font-semibold">{{ count($slots) }}</span></div>
        <div>Skala Preview: <span class="font-semibold">{{ $scale > 0 ? round($scale * 100) . '%' : '—' }}</span></div>
    </div>
</div>
