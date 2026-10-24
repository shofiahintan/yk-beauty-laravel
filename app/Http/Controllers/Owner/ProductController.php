<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

        $initialStock = (int) ($data['stok'] ?? 0);

        $product = new Product($data);
        $product->stok = 0; // Mulai dari 0 agar pencatatan stok awal tercatat via adjustStock
        $product->slug = $this->uniqueSlug($data['nama']);
        $product->is_active = $request->boolean('is_active', true);
        $product->image = $this->storeImage($request->file('image'));
        $product->save();

        Product::adjustStock($product, $initialStock, 'stok awal', null, auth()->id());

        $this->syncVariants($product, $request);
        $this->syncGallery($product, $request);

        return redirect()->route('owner.produk.index')->with('status', 'Produk "'.$product->nama.'" berhasil ditambahkan.');
    }

    public function show(Product $product)
    {
        $product->load(['variants.images', 'images']);

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
            'variants'      => $product->variants->map(fn ($v) => [
                'id'     => $v->id,
                'label'  => $v->label,
                'images' => $v->images->map(fn ($img) => [
                    'id'  => $img->id,
                    'url' => asset($img->path),
                ])->values(),
            ])->values(),
            'gallery'       => $product->images->map(fn ($img) => [
                'id'  => $img->id,
                'url' => asset($img->path),
            ])->values(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateData($request, true, $product);

        $oldStock = (int) $product->stok;
        $newStock = isset($data['stok']) ? (int) $data['stok'] : $oldStock;
        unset($data['stok']);

        $product->fill($data);
        $product->is_active = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $oldImage = $product->image;
            $product->image = $this->storeImage($request->file('image'));
            $this->safeDeleteImageFile($oldImage, null, $product->id);
        }

        $product->save();

        $delta = $newStock - $oldStock;
        if ($delta !== 0) {
            Product::adjustStock($product, $delta, 'edit owner', null, auth()->id());
        }

        $this->syncVariants($product, $request);
        $this->removeGalleryImages($product, $request);
        $this->syncGallery($product, $request);

        return redirect()->route('owner.produk.index')->with('status', 'Produk "'.$product->nama.'" berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $nama = $product->nama;

        // Ambil semua foto galeri umum & foto varian produk ini
        $images = ProductImage::where('product_id', $product->id)->get();
        foreach ($images as $img) {
            $path = $img->path;
            $img->delete();
            $this->safeDeleteImageFile($path);
        }

        $mainImage = $product->image;
        $product->variants()->delete();
        $product->delete();
        $this->safeDeleteImageFile($mainImage);

        return redirect()->route('owner.produk.index')->with('status', 'Produk "'.$nama.'" berhasil dihapus.');
    }

    public function toggleStatus(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('status', 'Status produk "'.$product->nama.'" diubah menjadi '.($product->is_active ? 'Aktif' : 'Nonaktif').'.');
    }

    private function validateData(Request $request, bool $isUpdate, ?Product $product = null): array
    {
        $validated = $request->validate([
            'nama'                 => ['required', 'string', 'max:255'],
            'brand'                => ['required', 'string', 'max:100'],
            'kategori'             => ['required', 'string', 'max:100'],
            'harga_normal'         => ['required', 'integer', 'min:1'],
            'diskon_persen'        => ['nullable', 'integer', 'min:0', 'max:90'],
            'badge'                => ['nullable', 'string', 'max:50'],
            'badge_class'          => ['nullable', 'string', 'max:50'],
            'deskripsi'            => ['nullable', 'string'],
            'stok'                 => ['required', 'integer', 'min:0'],
            'image'                => [$isUpdate ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'galeri'               => ['nullable', 'array'],
            'galeri.*'             => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'varian'               => ['nullable', 'array'],
            'varian.*'             => ['nullable'],
            'varian.*.id'          => ['nullable'],
            'varian.*.label'       => ['nullable', 'string', 'max:100'],
            'varian.*.foto'        => ['nullable', 'array'],
            'varian.*.foto.*'      => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'varian_foto'          => ['nullable', 'array'],
            'varian_foto.*.*'      => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_galeri'         => ['nullable', 'array'],
            'hapus_galeri.*'       => ['integer'],
            'hapus_foto_varian'    => ['nullable', 'array'],
            'hapus_foto_varian.*'  => ['integer'],
        ], [
            'image.required'        => 'Foto utama produk wajib diunggah.',
            'image.image'           => 'File foto utama harus berupa gambar.',
            'image.mimes'           => 'Format foto utama harus JPG, JPEG, PNG, atau WEBP.',
            'image.max'             => 'Ukuran foto utama maksimal 2 MB.',
            'galeri.*.image'        => 'File foto galeri harus berupa gambar.',
            'galeri.*.mimes'        => 'Format foto galeri harus JPG, JPEG, PNG, atau WEBP.',
            'galeri.*.max'          => 'Ukuran foto galeri maksimal 2 MB per file.',
            'varian.*.foto.*.image' => 'File foto varian harus berupa gambar.',
            'varian.*.foto.*.mimes' => 'Format foto varian harus JPG, JPEG, PNG, atau WEBP.',
            'varian.*.foto.*.max'   => 'Ukuran foto varian maksimal 2 MB per file.',
            'varian_foto.*.*.image' => 'File foto varian harus berupa gambar.',
            'varian_foto.*.*.mimes' => 'Format foto varian harus JPG, JPEG, PNG, atau WEBP.',
            'varian_foto.*.*.max'   => 'Ukuran foto varian maksimal 2 MB per file.',
        ]);

        // Validasi batas maksimal 8 foto galeri per produk (foto umum)
        $existingGalleryCount = 0;
        if ($product) {
            $removeIds = $request->input('hapus_galeri', []);
            $existingGalleryCount = $product->images()
                ->whereNotIn('id', is_array($removeIds) ? $removeIds : [])
                ->count();
        }
        $newGalleryCount = $request->hasFile('galeri') ? count($request->file('galeri')) : 0;
        if (($existingGalleryCount + $newGalleryCount) > 8) {
            throw ValidationException::withMessages([
                'galeri' => 'Maksimal 8 foto galeri per produk.',
            ]);
        }

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

    /**
     * Memeriksa apakah file gambar aman untuk dihapus dari filesystem.
     * Tidak boleh dihapus jika path masih dirujuk oleh order_items (riwayat pesanan)
     * atau masih dipakai oleh produk lain / product_images lain.
     */
    protected function canDeleteImageFile(?string $path, ?int $excludeProductImageId = null, ?int $excludeProductId = null): bool
    {
        if (! $path || ! str_starts_with($path, $this->uploadDir)) {
            // Jangan hapus aset seed data (mis. assets/images/produk/...)
            return false;
        }

        // Cek apakah masih dirujuk riwayat pesanan (order_items)
        if (OrderItem::where('image', $path)->exists()) {
            return false;
        }

        // Cek apakah masih dipakai foto utama produk lain
        $productQuery = Product::where('image', $path);
        if ($excludeProductId) {
            $productQuery->where('id', '!=', $excludeProductId);
        }
        if ($productQuery->exists()) {
            return false;
        }

        // Cek apakah masih dipakai baris product_images lain
        $imageQuery = ProductImage::where('path', $path);
        if ($excludeProductImageId) {
            $imageQuery->where('id', '!=', $excludeProductImageId);
        }
        if ($imageQuery->exists()) {
            return false;
        }

        return true;
    }

    protected function safeDeleteImageFile(?string $path, ?int $excludeProductImageId = null, ?int $excludeProductId = null): void
    {
        if ($this->canDeleteImageFile($path, $excludeProductImageId, $excludeProductId)) {
            $fullPath = public_path($path);
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }
    }

    /**
     * Sinkronisasi varian berbasis ID.
     * - Varian lama yang ada di-update (label dan urutan sort_order).
     * - Varian baru dibuat.
     * - Varian yang hilang dari input dihapus beserta seluruh foto dan file fisiknya (jika aman).
     * - Upload foto baru per varian disimpan ke product_images dengan product_variant_id terisi.
     */
    private function syncVariants(Product $product, Request $request): void
    {
        $rawVariants = $request->input('varian', []);
        if (! is_array($rawVariants)) {
            $rawVariants = [];
        }

        $submitted = [];
        foreach ($rawVariants as $index => $item) {
            if (is_array($item)) {
                $label = trim((string) ($item['label'] ?? ''));
                $id = ! empty($item['id']) ? (int) $item['id'] : null;
            } else {
                $label = trim((string) $item);
                $id = null;
            }

            if ($label === '') {
                continue;
            }

            $submitted[] = [
                'id'    => $id,
                'label' => $label,
                'index' => $index,
            ];
        }

        $existingVariants = $product->variants()->get()->keyBy('id');
        $submittedIds = array_filter(array_column($submitted, 'id'));

        // 1. Hapus varian yang dihilangkan dari form beserta foto & file fisiknya
        $variantsToDelete = $existingVariants->except($submittedIds);
        foreach ($variantsToDelete as $varToDelete) {
            $varImages = ProductImage::where('product_id', $product->id)
                ->where('product_variant_id', $varToDelete->id)
                ->get();

            foreach ($varImages as $vImg) {
                $path = $vImg->path;
                $vImg->delete();
                $this->safeDeleteImageFile($path);
            }

            $varToDelete->delete();
        }

        // 2. Hapus foto varian spesifik yang dicentang hapus lewat hapus_foto_varian[]
        // Dibatasi HANYA pada product_id produk ini
        $removeVariantImageIds = $request->input('hapus_foto_varian', []);
        if (! empty($removeVariantImageIds) && is_array($removeVariantImageIds)) {
            $imagesToDelete = ProductImage::where('product_id', $product->id)
                ->whereNotNull('product_variant_id')
                ->whereIn('id', $removeVariantImageIds)
                ->get();

            foreach ($imagesToDelete as $img) {
                $path = $img->path;
                $img->delete();
                $this->safeDeleteImageFile($path);
            }
        }

        // 3. Update varian yang sudah ada atau buat varian baru
        $processedVariants = [];
        foreach ($submitted as $order => $data) {
            if ($data['id'] && isset($existingVariants[$data['id']])) {
                $variant = $existingVariants[$data['id']];
                $variant->label = $data['label'];
                $variant->sort_order = $order;
                $variant->save();
            } else {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'label'      => $data['label'],
                    'sort_order' => $order,
                ]);
            }
            $processedVariants[$data['index']] = $variant;
        }

        // 4. Upload foto khusus varian
        foreach ($submitted as $data) {
            $index = $data['index'];
            $variant = $processedVariants[$index] ?? null;
            if (! $variant) {
                continue;
            }

            $files = $request->file("varian.{$index}.foto")
                ?? $request->file("varian_foto.{$index}")
                ?? [];

            if (! is_array($files)) {
                $files = $files ? [$files] : [];
            }

            $lastSort = (int) ($variant->images()->max('sort_order') ?? -1);
            foreach ($files as $fIndex => $file) {
                if ($file && $file->isValid()) {
                    $path = $this->storeImage($file);
                    ProductImage::create([
                        'product_id'         => $product->id,
                        'product_variant_id' => $variant->id,
                        'path'               => $path,
                        'sort_order'         => $lastSort + 1 + $fIndex,
                    ]);
                }
            }
        }
    }

    private function syncGallery(Product $product, Request $request): void
    {
        if (! $request->hasFile('galeri')) {
            return;
        }

        $lastOrder = $product->images()->max('sort_order') ?? -1;

        foreach ($request->file('galeri') as $i => $file) {
            if ($file && $file->isValid()) {
                $path = $this->storeImage($file);
                ProductImage::create([
                    'product_id'         => $product->id,
                    'product_variant_id' => null,
                    'path'               => $path,
                    'sort_order'         => $lastOrder + 1 + $i,
                ]);
            }
        }
    }

    // Hapus foto galeri yang dicentang "hapus" di form edit (id dikirim lewat hapus_galeri[]).
    // Dibatasi HANYA untuk product_id yang sedang diedit dan foto umum (bukan varian).
    private function removeGalleryImages(Product $product, Request $request): void
    {
        $ids = $request->input('hapus_galeri', []);

        if (empty($ids) || ! is_array($ids)) {
            return;
        }

        $images = ProductImage::where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->whereIn('id', $ids)
            ->get();

        foreach ($images as $img) {
            $path = $img->path;
            $img->delete();
            $this->safeDeleteImageFile($path);
        }
    }
}
