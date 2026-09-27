<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title') — YK Beauty & Skincare</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ @filemtime(public_path('css/style.css')) ?: time() }}">
</head>
<body>

@include('partials.navbar')

@yield('content')

@include('partials.footer')

@yield('inline-data')

<script src="{{ asset('js/toast.js') }}"></script>
<script src="{{ asset('js/cart.js') }}"></script>
<script src="{{ asset('js/main.js') }}"></script>
@stack('scripts')

{{-- Chat pembeli–owner: hanya tampil untuk pelanggan yang sudah login --}}
@include('partials.chat-widget')

{{-- Modal ulasan wajib: hanya tampil untuk pelanggan yang sudah login --}}
@include('partials.review-required-modal')

</body>
</html>