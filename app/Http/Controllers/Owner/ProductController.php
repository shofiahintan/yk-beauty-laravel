<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // Semua gambar produk disimpan di public/ (bukan storage/), biar konsisten
    // dengan asset($product->image) yang sudah dipakai di seluruh storefront.
    private string $uploadDir = 'assets/images/products';

    public function index(Request $request)
    {
        $query = Product::query();

        if ($cari = $request->query('cari')) {
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                    ->orWhere('brand', 'like', "%{$cari}%")
                    ->orWhere('kategori', 'like', "%{$cari}%");
            });
        }

        if ($kategori = $request->query('kategori')) {
            $query->where('kategori', $kategori);
        }

        if ($status = $request->query('status')) {
            match ($status) {
                'aktif' => $query->where('is_active', true),
                'nonaktif' => $query->where('is_active', false),
                'menipis' => $query->lowStock(),
                default => null,
            };
        }

        $products = $query->orderByDesc('id')->paginate(10)->withQueryString();
        $kategoriList = Product::query()->select('kategori')->distinct()->orderBy('kategori')->pluck('kategori');

        return view('owner.produk.index', [
            'products' => $products,
            'kategoriList' => $kategoriList,
            'filters' => $request->only(['cari', 'kategori', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request, false);

        $product = new Product($data);
        $product->slug = $this->uniqueSlug($data['nama']);
        $product->is_active = $request->boolean('is_active', true);
        $product->image = $this->storeImage($request->file('image'));
        $product->save();

        $this->syncVariants($product, $request);
        $this->syncGallery($product, $request);

        return redirect()->route('owner.produk.index')->with('status', 'Produk "'.$product->nama.'" berhasil ditambahkan.');
    }

    public function show(Product $product)
    {
        $product->load(['variants', 'images']);

        return response()->json([
            'id'            => $product->id,
            'nama'          => $product->nama,
            'brand'         => $product->brand,
            'kategori'      => $product->kategori,
            'harga'         => $product->harga,
            'harga_normal'  => $product->harga_normal,
            'diskon_persen' => $product->diskon_persen,
            'coret'         => $product->coret,
            'badge'         => $product->badge,
            'badge_class'   => $product->badge_class,
            'deskripsi'     => $product->deskripsi,
            'stok'          => $product->stok,
            'is_active'     => $product->is_active,
            'image'         => $product->image,
            'image_url'     => asset($product->image),
            'variants'      => $product->variants->pluck('label')->values(),
            'gallery'       => $product->images->map(fn ($img) => [
                'id'  => $img->id,
                'url' => asset($img->path),
            ])->values(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateData($request, true);

        $product->fill($data);
        $product->is_active = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $this->deleteImageFile($product->image);
            $product->image = $this->storeImage($request->file('image'));
        }

        $product->save();

        $this->syncVariants($product, $request);
        $this->syncGallery($product, $request);
        $this->removeGalleryImages($request);

        return redirect()->route('owner.produk.index')->with('status', 'Produk "'.$product->nama.'" berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $nama = $product->nama;

        foreach ($product->images as $img) {
            $this->deleteImageFile($img->path);
        }
        $this->deleteImageFile($product->image);

        $product->delete();

        return redirect()->route('owner.produk.index')->with('status', 'Produk "'.$nama.'" berhasil dihapus.');
    }

    public function toggleStatus(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('status', 'Status produk "'.$product->nama.'" diubah menjadi '.($product->is_active ? 'Aktif' : 'Nonaktif').'.');
    }

    private function validateData(Request $request, bool $isUpdate): array
    {
        $validated = $request->validate([
            'nama'          => ['required', 'string', 'max:255'],
            'brand'         => ['required', 'string', 'max:100'],
            'kategori'      => ['required', 'string', 'max:100'],
            'harga_normal'  => ['required', 'integer', 'min:1'],
            'diskon_persen' => ['nullable', 'integer', 'min:0', 'max:90'],
            'badge'         => ['nullable', 'string', 'max:50'],
            'badge_class'   => ['nullable', 'string', 'max:50'],
            'deskripsi'     => ['nullable', 'string'],
            'stok'          => ['required', 'integer', 'min:0'],
            'image'         => [$isUpdate ? 'nullable' : 'required', 'image', 'max:2048'],
            'varian'        => ['nullable', 'array'],
            'varian.*'      => ['nullable', 'string', 'max:100'],
            'galeri'        => ['nullable', 'array'],
            'galeri.*'      => ['image', 'max:2048'],
        ]);

        $diskon = isset($validated['diskon_persen']) && $validated['diskon_persen'] !== '' ? (int) $validated['diskon_persen'] : null;
        $validated['diskon_persen'] = $diskon;
        $validated['harga'] = Product::calculateSellingPrice((int) $validated['harga_normal'], $diskon);

        return $validated;
    }

    private function uniqueSlug(string $nama): string
    {
        $base = Str::slug($nama);
        $slug = $base;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    private function storeImage($file): string
    {
        $filename = uniqid('prod_').'.'.$file->getClientOriginalExtension();
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
            // Hanya hapus file yang memang di-upload lewat panel ini (folder uploadDir),
            // biar gambar seed data lama (assets/images/*.png) gak ikut kehapus.
            File::delete(public_path($path));
        }
    }

    // Varian di-submit sebagai daftar label lengkap tiap kali form disubmit (repeater di UI),
    // jadi paling aman: hapus semua baris lama lalu buat ulang sesuai input terbaru.
    private function syncVariants(Product $product, Request $request): void
    {
        if (! $request->has('varian')) {
            return;
        }

        $labels = array_values(array_filter(
            $request->input('varian', []),
            fn ($v) => trim((string) $v) !== ''
        ));

        $product->variants()->delete();

        foreach ($labels as $i => $label) {
            ProductVariant::create([
                'product_id' => $product->id,
                'label' => trim($label),
                'sort_order' => $i,
            ]);
        }
    }

    private function syncGallery(Product $product, Request $request): void
    {
        if (! $request->hasFile('galeri')) {
            return;
        }

        $lastOrder = $product->images()->max('sort_order') ?? -1;

        foreach ($request->file('galeri') as $i => $file) {
            $path = $this->storeImage($file);
            ProductImage::create([
                'product_id' => $product->id,
                'product_variant_id' => null,
                'path' => $path,
                'sort_order' => $lastOrder + 1 + $i,
            ]);
        }
    }

    // Hapus foto galeri yang dicentang "hapus" di form edit (id dikirim lewat hapus_galeri[]).
    private function removeGalleryImages(Request $request): void
    {
        $ids = $request->input('hapus_galeri', []);

        if (empty($ids)) {
            return;
        }

        $images = ProductImage::whereIn('id', $ids)->get();

        foreach ($images as $img) {
            $this->deleteImageFile($img->path);
            $img->delete();
        }
    }
}
