@extends('layouts.app')

@section('title', $article ? $article->title : __('blog_not_found_title'))

@section('content')

@if (!$article)

  <section class="detail-not-found">
    <div class="container">
      <h2>{{ __('blog_not_found_title') }}</h2>
      <p>{{ __('blog_not_found_desc') }}</p>
      <a href="{{ route('blog') }}" class="btn btn-primary">{{ __('blog_back_to_list') }}</a>
    </div>
  </section>

@else

  <section class="blog-detail-section">
    <div class="container">

      <div class="blog-detail-head">
        <div class="blog-meta-row">
          <span class="blog-badge">{{ $article->category }}</span>
          <span class="blog-date">{{ yk_format_blog_date($article->published_at) }}</span>
        </div>
        <h1>{{ $article->title }}</h1>
      </div>

      <div class="blog-detail-img">
        <img src="{{ asset($article->image) }}" alt="{{ $article->title }}">
      </div>

      <div class="blog-detail-body">
        @foreach ($article->body as $p)
          <p>{{ $p }}</p>
        @endforeach

        <a href="{{ route('blog') }}" class="blog-back-link">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"/></svg>
          {{ __('blog_back_to_list') }}
        </a>
      </div>

      @if ($related->isNotEmpty())
        <div class="blog-related">
          <h2>{{ __('blog_related_title') }}</h2>
          <div class="blog-related-grid">
            @foreach ($related as $a)
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
        </div>
      @endif

    </div>
  </section>

@endif

@endsection
