@php
    $url = asset('storage/' . $getRecord()->path);
    $placements = $getRecord()->placements->pluck('placement')->toArray();

    $isVideo = in_array('video_story', $placements) || in_array('video_cinematic', $placements);
@endphp

@if($isVideo)
    <video width="80" height="50" style="border-radius:6px; object-fit:cover;" muted>
        <source src="{{ $url }}" type="video/mp4">
    </video>
@else
    <img src="{{ $url }}" width="50" height="50" style="border-radius:50%; object-fit:cover;" />
@endif