<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    private string $uploadDir = 'assets/images/collections';

    public function index(Request $request)
    {
        $collections = Collection::query()
            ->withCount('products')
            ->with(['products:products.id'])
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $allProducts = Product::query()->orderBy('nama')->get(['id', 'nama', 'brand', 'image']);

        return view('owner.produk.koleksi', [
            'collections' => $collections,
            'allProducts' => $allProducts,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request, false);

        $collection = new Collection([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'harga' => $data['harga'],
            'harga_coret' => $data['harga_coret'] ?? null,
        ]);
        $collection->slug = $this->uniqueSlug($data['title']);
        $collection->is_active = $request->boolean('is_active', true);
        $collection->image = $this->storeImage($request->file('image'));
        $collection->save();

        $this->syncProducts($collection, $request);

        return redirect()->route('owner.koleksi.index')->with('status', 'Koleksi "'.$collection->title.'" berhasil ditambahkan.');
    }

    public function update(Request $request, Collection $collection)
    {
        $data = $this->validateData($request, true);

        $collection->title = $data['title'];
        $collection->description = $data['description'] ?? null;
        $collection->harga = $data['harga'];
        $collection->harga_coret = $data['harga_coret'] ?? null;
        $collection->is_active = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $this->deleteImageFile($collection->image);
            $collection->image = $this->storeImage($request->file('image'));
        }

        $collection->save();

        $this->syncProducts($collection, $request);

        return redirect()->route('owner.koleksi.index')->with('status', 'Koleksi "'.$collection->title.'" berhasil diperbarui.');
    }

    public function destroy(Collection $collection)
    {
        $title = $collection->title;

        $this->deleteImageFile($collection->image);
        $collection->products()->detach();
        $collection->delete();

        return redirect()->route('owner.koleksi.index')->with('status', 'Koleksi "'.$title.'" berhasil dihapus.');
    }

    private function validateData(Request $request, bool $isUpdate): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'harga' => ['required', 'integer', 'min:0'],
            'harga_coret' => ['nullable', 'integer', 'min:0'],
            'image' => [$isUpdate ? 'nullable' : 'required', 'image', 'max:2048'],
            'produk' => ['required', 'array', 'min:1'],
            'produk.*' => ['integer', 'exists:products,id'],
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (Collection::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    private function storeImage($file): string
    {
        $filename = uniqid('koleksi_').'.'.$file->getClientOriginalExtension();
        $dest = public_path($this->uploadDir);

        if (! File::isDirectory($dest)) {
            File::makeDirectory($dest, 0755, true);
        }

        $file->move($dest, $filename);

        return $this->uploadDir.'/'.$filename;
    }

    private function deleteImageFile(?string $path): void
    {
        if ($path && str_starts_with($path, $this->uploadDir) && File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }

    private function syncProducts(Collection $collection, Request $request): void
    {
        $ids = $request->input('produk', []);
        $sync = [];

        foreach (array_values($ids) as $i => $id) {
            $sync[$id] = ['sort_order' => $i];
        }

        $collection->products()->sync($sync);
    }
}
