@php
    $s = $settings;

    $scale = min(1, 560 / max($s['paper_width_mm'], 1));

    $pw = $s['paper_width_mm'] * $scale;
    $ph = $s['paper_height_mm'] * $scale;

    $contentW = max(0, $s['paper_width_mm'] - $s['margin_left_mm'] - $s['margin_right_mm']);
    $contentH = max(0, $s['paper_height_mm'] - $s['margin_top_mm'] - $s['margin_bottom_mm']);

    $headerOffset = $s['show_header'] ? $s['header_height_mm'] : 0;

    $labelsPerPage = $s['columns'] * $s['rows'];

    $overflowLabels = [];
    for ($r = 0; $r < $s['rows']; $r++) {
        for ($c = 0; $c < $s['columns']; $c++) {
            $left = $s['margin_left_mm'] + $c * ($s['label_width_mm'] + $s['gap_x_mm']);
            $top = $s['margin_top_mm'] + $r * ($s['label_height_mm'] + $s['gap_y_mm']) + $headerOffset;

            $overflowLabels[] = ($left + $s['label_width_mm'] > $s['paper_width_mm'] - $s['margin_right_mm'])
                || ($top + $s['label_height_mm'] > $s['paper_height_mm'] - $s['margin_bottom_mm']);
        }
    }

    $hasOverflow = in_array(true, $overflowLabels, true);
    $borderStyle = $s['border_style'] === 'none' ? 'solid' : $s['border_style'];
@endphp

<div class="space-y-3">
    @if ($hasOverflow)
        <div class="rounded-lg bg-danger-50 p-3 text-xs text-danger-700 dark:bg-danger-400/10 dark:text-danger-400">
            Ada card yang melebihi area kertas. Kurangi jumlah kolom/baris, perkecil ukuran card, atau naikkan margin.
        </div>
    @endif

    <div class="flex justify-center overflow-x-auto rounded-lg border border-gray-200 bg-gray-100 p-4 dark:border-white/10 dark:bg-gray-900">
        <div
            style="flex: none; position: relative; width: {{ $pw }}px; height: {{ $ph }}px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2);">
            {{-- area konten (di dalam margin) --}}
            <div
                style="position: absolute; left: {{ $s['margin_left_mm'] * $scale }}px; top: {{ $s['margin_top_mm'] * $scale }}px; width: {{ $contentW * $scale }}px; height: {{ $contentH * $scale }}px; border: 1px dashed #94a3b8; z-index: 1;">
            </div>

            @php $i = 0; @endphp
            @for ($r = 0; $r < $s['rows']; $r++)
                @for ($c = 0; $c < $s['columns']; $c++)
                    @php
                        $left = $s['margin_left_mm'] + $c * ($s['label_width_mm'] + $s['gap_x_mm']);
                        $top = $s['margin_top_mm'] + $r * ($s['label_height_mm'] + $s['gap_y_mm']) + $headerOffset;
                        $overflow = $overflowLabels[$i];
                        $i++;
                    @endphp
                    <div
                        style="position: absolute; left: {{ $left * $scale }}px; top: {{ $top * $scale }}px; width: {{ $s['label_width_mm'] * $scale }}px; height: {{ $s['label_height_mm'] * $scale }}px; border: 1px {{ $borderStyle }} {{ $overflow ? '#ef4444' : '#cbd5e1' }}; border-radius: {{ $s['corner_radius_mm'] * $scale }}px; background: {{ $overflow ? 'rgba(239,68,68,.12)' : '#f8fafc' }}; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #94a3b8; z-index: 2;">
                        <span>Card {{ $i }}</span>
                    </div>
                @endfor
            @endfor
        </div>
    </div>

    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-600 dark:text-gray-300">
        <div>Kertas: <span class="font-semibold">{{ $s['paper_width_mm'] }} × {{ $s['paper_height_mm'] }} mm</span></div>
        <div>Card: <span class="font-semibold">{{ $s['label_width_mm'] }} × {{ $s['label_height_mm'] }} mm</span></div>
        <div>Margin: <span class="font-semibold">atas {{ $s['margin_top_mm'] }} · kanan {{ $s['margin_right_mm'] }} · bawah {{ $s['margin_bottom_mm'] }} · kiri {{ $s['margin_left_mm'] }}</span></div>
        <div>Gap: <span class="font-semibold">X {{ $s['gap_x_mm'] }} · Y {{ $s['gap_y_mm'] }} mm</span></div>
        <div>Layout: <span class="font-semibold">{{ $s['columns'] }} kolom × {{ $s['rows'] }} baris</span></div>
        <div>Card per halaman: <span class="font-semibold">{{ $labelsPerPage }}</span></div>
    </div>
</div>
