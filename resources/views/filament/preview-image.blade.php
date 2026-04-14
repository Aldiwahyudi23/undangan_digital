<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Preview Gambar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Kalau pakai Tailwind (default Filament sudah include) --}}
</head>
<body class="bg-gray-50">

    <div class="flex items-center justify-center w-full">
        <div class="w-full text-center">

            {{-- Gambar --}}
            <img 
                src="{{ $image }}" 
                alt="Preview Gambar"
                class="mx-auto rounded-xl shadow-xl transition duration-300 hover:scale-105"
                style="max-height: 75vh; object-fit: contain;"
            >

            {{-- Info tambahan (opsional) --}}
            <p class="mt-4 text-sm text-gray-500">
                Klik di luar untuk menutup preview
            </p>

        </div>
    </div>

</body>
</html>