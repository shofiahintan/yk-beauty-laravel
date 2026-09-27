@extends('layouts.app')

@section('title', __('blog_meta_title'))

@section('content')

<section class="page-banner">
  <div class="container">
    <div class="inner">
      <h1>{{ __('blog_title') }}</h1>
      <p>{{ __('blog_desc') }}</p>
    </div>
  </div>
</section>

<section class="blog-section">
  <div class="container">

    <div class="blog-grid">
      @foreach ($articles as $a)
        <article class="blog-card">
          <a href="{{ route('blog.detail', $a->slug) }}" class="blog-card-img">
            <img src="{{ asset($a->image) }}" alt="{{ $a->title }}" loading="lazy">
          </a>
          <div class="blog-card-body">
            <div class="blog-meta-row">
              <span class="blog-badge">{{ $a->category }}</span>
              <span class="blog-date">{{ yk_format_blog_date($a->published_at) }}</span>
            </div>
            <h3><a href="{{ route('blog.detail', $a->slug) }}">{{ $a->title }}</a></h3>
            <p>{{ $a->excerpt }}</p>
          </div>
        </article>
      @endforeach
    </div>

    @if ($articles->hasPages())
      <nav class="blog-pagination" aria-label="Pagination">
        @if ($articles->onFirstPage())
          <button type="button" class="blog-page-btn" disabled>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
          </button>
        @else
          <a href="{{ $articles->previousPageUrl() }}" class="blog-page-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
          </a>
        @endif

        @for ($i = 1; $i <= $articles->lastPage(); $i++)
          <a href="{{ $articles->url($i) }}" class="blog-page-btn {{ $articles->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
        @endfor

        @if ($articles->hasMorePages())
          <a href="{{ $articles->nextPageUrl() }}" class="blog-page-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
          </a>
        @else
          <button type="button" class="blog-page-btn" disabled>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
          </button>
        @endif
      </nav>
    @endif

  </div>
</section>

@endsection
