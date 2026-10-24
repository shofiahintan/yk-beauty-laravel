<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    private string $uploadDir = 'uploads/blog';

    public function index(Request $request)
    {
        $query = BlogPost::query();

        if ($request->filled('kategori')) {
            $query->where('category', $request->kategori);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($b) use ($q) {
                $b->where('title', 'like', "%{$q}%")
                  ->orWhere('excerpt', 'like', "%{$q}%");
            });
        }

        $posts = $query->orderByDesc('published_at')->orderByDesc('id')->paginate(10)->withQueryString();
        $categories = BlogPost::select('category')->distinct()->pluck('category')->filter()->values();

        return view('owner.artikel.index', [
            'posts'      => $posts,
            'categories' => $categories,
            'filters'    => $request->only(['kategori', 'q']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'category'     => ['required', 'string', 'max:100'],
            'published_at' => ['required', 'date'],
            'image'        => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'excerpt'      => ['required', 'string', 'max:500'],
            'body'         => ['required', 'string'],
        ], [
            'image.max'   => 'Ukuran gambar maksimal 2 MB.',
            'image.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
        ]);

        $imagePath = $this->storeImage($request->file('image'));
        $slug = $this->uniqueSlug($validated['title']);
        $bodyParagraphs = $this->parseBodyParagraphs($validated['body']);

        $post = BlogPost::create([
            'title'        => $validated['title'],
            'slug'         => $slug,
            'category'     => $validated['category'],
            'published_at' => $validated['published_at'],
            'image'        => $imagePath,
            'excerpt'      => $validated['excerpt'],
            'body'         => $bodyParagraphs,
        ]);

        return redirect()->route('owner.artikel.index')
            ->with('status', 'Artikel "'.$post->title.'" berhasil ditambahkan.');
    }

    public function update(Request $request, BlogPost $artikel)
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'category'     => ['required', 'string', 'max:100'],
            'published_at' => ['required', 'date'],
            'image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'excerpt'      => ['required', 'string', 'max:500'],
            'body'         => ['required', 'string'],
        ], [
            'image.max'   => 'Ukuran gambar maksimal 2 MB.',
            'image.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
        ]);

        if ($request->hasFile('image')) {
            $this->deleteImageFile($artikel->image);
            $artikel->image = $this->storeImage($request->file('image'));
        }

        if ($artikel->title !== $validated['title']) {
            $artikel->slug = $this->uniqueSlug($validated['title'], $artikel->id);
        }

        $artikel->title        = $validated['title'];
        $artikel->category     = $validated['category'];
        $artikel->published_at = $validated['published_at'];
        $artikel->excerpt      = $validated['excerpt'];
        $artikel->body         = $this->parseBodyParagraphs($validated['body']);
        $artikel->save();

        return redirect()->route('owner.artikel.index')
            ->with('status', 'Artikel "'.$artikel->title.'" berhasil diperbarui.');
    }

    public function destroy(BlogPost $artikel)
    {
        $title = $artikel->title;
        $this->deleteImageFile($artikel->image);
        $artikel->delete();

        return redirect()->route('owner.artikel.index')
            ->with('status', 'Artikel "'.$title.'" berhasil dihapus.');
    }

    private function parseBodyParagraphs(string $rawBody): array
    {
        $paragraphs = preg_split('/\r?\n\s*\r?\n/', trim($rawBody));
        return array_values(array_filter(array_map('trim', $paragraphs), fn($p) => $p !== ''));
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'artikel';
        }
        $slug = $base;
        $i = 1;
        while (BlogPost::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    private function storeImage($file): string
    {
        $filename = uniqid('blog_').'.'.$file->getClientOriginalExtension();
        $dest = public_path($this->uploadDir);

        if (! File::isDirectory($dest)) {
            File::makeDirectory($dest, 0755, true);
        }

        $file->move($dest, $filename);

        return $this->uploadDir.'/'.$filename;
    }

    private function deleteImageFile(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'assets/') && File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }
}
