@php
$isVideo = in_array('video_story', $placements) || in_array('video_cinematic', $placements);
@endphp

<div style="text-align:center">
    @if($isVideo)
        <video controls style="width:100%; border-radius:12px;">
            <source src="{{ $file }}" type="video/mp4">
        </video>
    @else
        <img src="{{ $file }}" style="width:100%; border-radius:12px;" />
    @endif
</div>