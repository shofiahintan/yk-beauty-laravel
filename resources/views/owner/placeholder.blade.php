@extends('layouts.owner')

@section('title', $title)
@section('breadcrumb', $title)

@section('content')
    <h1 class="font-poppins font-semibold text-[24px] text-maroon-900 mb-8">{{ $title }}</h1>
    <div class="bg-white rounded-[18px] shadow-owner-card p-10 text-center">
        <p class="text-[14px] text-ink-soft">Halaman ini masih tahap berikutnya dalam rencana pengerjaan — belum diimplementasikan penuh.</p>
    </div>
@endsection
