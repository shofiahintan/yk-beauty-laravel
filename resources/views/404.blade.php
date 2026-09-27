@extends('layouts.app')

@section('title', __('notfound_meta_title'))

@section('content')

<section class="notfound-section">
  <div class="container">
    <div class="notfound-code">404</div>
    <h1>{{ __('notfound_title') }}</h1>
    <p>{{ __('notfound_desc') }}</p>
    <div class="notfound-btn-row">
      <a href="{{ route('home') }}" class="btn btn-primary">{{ __('notfound_back_home') }}</a>
      <a href="{{ route('produk') }}" class="btn btn-outline">{{ __('notfound_browse') }}</a>
    </div>
  </div>
</section>

@endsection
