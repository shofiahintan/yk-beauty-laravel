<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        $articles = BlogPost::orderByDesc('published_at')->paginate(9);

        return view('blog', [
            'articles' => $articles,
        ]);
    }

    public function show($slug = null)
    {
        $slug = $slug ?: request('slug');
        $article = BlogPost::where('slug', $slug)->first();

        $related = $article
            ? BlogPost::where('slug', '!=', $article->slug)->orderByDesc('published_at')->take(3)->get()
            : collect();

        return view('blog-detail', [
            'article' => $article,
            'related' => $related,
        ]);
    }
}
