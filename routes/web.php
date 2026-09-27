<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ===== HOME =====
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// ===== PRODUK =====
Route::get('/produk', [App\Http\Controllers\ProductController::class, 'index'])->name('produk');
Route::get('/produk/{slug}', [App\Http\Controllers\ProductController::class, 'show'])->name('produk.detail');

// ===== PAKET BUNDLING (dulu Koleksi) =====
Route::get('/paket-bundling', [App\Http\Controllers\CollectionController::class, 'index'])->name('koleksi');
Route::get('/paket-bundling/{slug}', [App\Http\Controllers\CollectionController::class, 'show'])->name('koleksi.detail');

// ===== BLOG =====
Route::get('/blog', [App\Http\Controllers\BlogController::class, 'index'])->name('blog');
Route::get('/blog/{slug}', [App\Http\Controllers\BlogController::class, 'show'])->name('blog.detail');

// ===== HALAMAN INFORMASI STATIS =====
Route::get('/tentang', [App\Http\Controllers\PageController::class, 'tentang'])->name('tentang');
Route::get('/syarat-ketentuan', [App\Http\Controllers\PageController::class, 'syarat'])->name('syarat');
Route::get('/kebijakan-privasi', [App\Http\Controllers\PageController::class, 'privasi'])->name('privasi');

// ===== BAHASA =====
Route::get('/lang/{locale}', function (\Illuminate\Http\Request $request, $locale) {
    if (in_array($locale, ['id', 'en'], true)) {
        \Illuminate\Support\Facades\Cookie::queue('site_lang', $locale, 60 * 24 * 365);
    }
    return redirect()->back();
})->name('lang.switch');

// ===== WISHLIST (bisa diakses tanpa login, data di localStorage) =====
Route::get('/wishlist', [App\Http\Controllers\WishlistController::class, 'index'])->name('wishlist');

// ===== KERANJANG (bisa diakses tanpa login, data di localStorage) =====
Route::get('/keranjang', [App\Http\Controllers\CartController::class, 'index'])->name('keranjang');

// ===== CHECKOUT =====
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [App\Http\Controllers\CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [App\Http\Controllers\CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success', [App\Http\Controllers\CheckoutController::class, 'success'])->name('checkout.success');
    // Halaman sukses pembayaran — wajib login, controller cek pemilik pesanan
    Route::get('/payment-sukses', [App\Http\Controllers\CheckoutController::class, 'success'])->name('payment.sukses');
});

// ===== MIDTRANS WEBHOOK (dipanggil server Midtrans, bukan browser) =====
Route::post('/midtrans/notification', [App\Http\Controllers\MidtransNotificationController::class, 'handle'])->name('midtrans.notification');

// ===== PESANAN (auth required — data pesanan berisi info pribadi pembeli) =====
Route::middleware('auth')->group(function () {
    Route::get('/pesanan', [App\Http\Controllers\OrderController::class, 'show'])->name('pesanan');
    Route::get('/riwayat-pesanan', [App\Http\Controllers\OrderController::class, 'history'])->name('riwayat');
    Route::post('/pesanan/{orderCode}/cancel', [App\Http\Controllers\OrderController::class, 'cancel'])->name('pesanan.cancel');
});

// ===== ULASAN (auth required) =====
Route::middleware('auth')->group(function () {
    Route::get('/review/pending', [App\Http\Controllers\ReviewController::class, 'pending'])->name('review.pending');
    Route::post('/review', [App\Http\Controllers\ReviewController::class, 'store'])->name('review.store');
});

// ===== AKUN (auth required) =====
Route::middleware('auth')->group(function () {
    Route::get('/akun', [App\Http\Controllers\AccountController::class, 'index'])->name('akun');
    Route::post('/akun/update', [App\Http\Controllers\AccountController::class, 'update'])->name('akun.update');
    Route::get('/pengaturan', [App\Http\Controllers\AccountController::class, 'settings'])->name('pengaturan');
    Route::post('/pengaturan/password', [App\Http\Controllers\AccountController::class, 'updatePassword'])->name('pengaturan.password');
});


// ===== CHAT PEMBELI–OWNER (auth required — widget di storefront) =====
Route::middleware('auth')->prefix('chat')->name('chat.')->group(function () {
    Route::get('/messages', [App\Http\Controllers\ChatController::class, 'index'])->name('index');
    Route::post('/messages', [App\Http\Controllers\ChatController::class, 'store'])->middleware('throttle:40,1')->name('store');
    Route::get('/unread', [App\Http\Controllers\ChatController::class, 'unread'])->name('unread');
    // Foto chat: dilayani lewat controller (bukan URL publik) — cek pemilik ada di dalamnya
    Route::get('/image/{message}', [App\Http\Controllers\ChatController::class, 'image'])->whereNumber('message')->name('image');
});

// ===== DASHBOARD (Breeze default) — owner ke panel, customer ke /akun =====
Route::get('/dashboard', function () {
    return auth()->user()->isOwner()
        ? redirect()->route('owner.dashboard')
        : redirect()->route('akun');
})->middleware(['auth', 'verified'])->name('dashboard');

// ===== OWNER PANEL (internal, khusus role owner) =====
Route::prefix('owner')->name('owner.')->middleware(['auth', 'owner'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Owner\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/produk', [App\Http\Controllers\Owner\ProductController::class, 'index'])->name('produk.index');
    Route::post('/produk', [App\Http\Controllers\Owner\ProductController::class, 'store'])->name('produk.store');
    Route::get('/produk/{product}', [App\Http\Controllers\Owner\ProductController::class, 'show'])->name('produk.show');
    Route::put('/produk/{product}', [App\Http\Controllers\Owner\ProductController::class, 'update'])->name('produk.update');
    Route::delete('/produk/{product}', [App\Http\Controllers\Owner\ProductController::class, 'destroy'])->name('produk.destroy');
    Route::patch('/produk/{product}/toggle-status', [App\Http\Controllers\Owner\ProductController::class, 'toggleStatus'])->name('produk.toggle-status');

    Route::get('/paket-bundling', [App\Http\Controllers\Owner\CollectionController::class, 'index'])->name('koleksi.index');
    Route::post('/paket-bundling', [App\Http\Controllers\Owner\CollectionController::class, 'store'])->name('koleksi.store');
    Route::put('/paket-bundling/{collection}', [App\Http\Controllers\Owner\CollectionController::class, 'update'])->name('koleksi.update');
    Route::delete('/paket-bundling/{collection}', [App\Http\Controllers\Owner\CollectionController::class, 'destroy'])->name('koleksi.destroy');

    Route::get('/transaksi', [App\Http\Controllers\Owner\OrderController::class, 'index'])->name('transaksi.index');
    Route::get('/transaksi/{order}', [App\Http\Controllers\Owner\OrderController::class, 'show'])->name('transaksi.show');
    Route::patch('/transaksi/{order}', [App\Http\Controllers\Owner\OrderController::class, 'update'])->name('transaksi.update');

    Route::get('/laporan', [App\Http\Controllers\Owner\ReportController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/unduh', [App\Http\Controllers\Owner\ReportController::class, 'download'])->name('laporan.download');

    Route::get('/pengguna', [App\Http\Controllers\Owner\UserController::class, 'index'])->name('pengguna.index');
    Route::get('/pengguna/{user}', [App\Http\Controllers\Owner\UserController::class, 'show'])->name('pengguna.show');
    Route::patch('/pengguna/{user}/toggle-status', [App\Http\Controllers\Owner\UserController::class, 'toggleStatus'])->name('pengguna.toggle-status');

    Route::get('/notifikasi', [App\Http\Controllers\Owner\NotificationController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/buka/{type}/{id}', [App\Http\Controllers\Owner\NotificationController::class, 'open'])
        ->whereIn('type', ['order', 'stock'])->whereNumber('id')->name('notifikasi.open');
    Route::post('/notifikasi/baca-semua', [App\Http\Controllers\Owner\NotificationController::class, 'readAll'])->name('notifikasi.read-all');

    Route::get('/profil', [App\Http\Controllers\Owner\ProfileController::class, 'edit'])->name('profil.edit');
    Route::patch('/profil', [App\Http\Controllers\Owner\ProfileController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [App\Http\Controllers\Owner\ProfileController::class, 'updatePassword'])->name('profil.password');

    Route::get('/cari', [App\Http\Controllers\Owner\SearchController::class, 'index'])->name('cari');

    Route::get('/pesan', [App\Http\Controllers\Owner\ChatController::class, 'index'])->name('pesan.index');
    Route::get('/pesan/threads', [App\Http\Controllers\Owner\ChatController::class, 'threads'])->name('pesan.threads');
    Route::get('/pesan/unread', [App\Http\Controllers\Owner\ChatController::class, 'unread'])->name('pesan.unread');
    Route::get('/pesan/{user}', [App\Http\Controllers\Owner\ChatController::class, 'show'])->whereNumber('user')->name('pesan.show');
    Route::get('/pesan/{user}/messages', [App\Http\Controllers\Owner\ChatController::class, 'messages'])->whereNumber('user')->name('pesan.messages');
    Route::post('/pesan/{user}/messages', [App\Http\Controllers\Owner\ChatController::class, 'send'])->whereNumber('user')->middleware('throttle:60,1')->name('pesan.send');
});

// ===== PROFILE (Breeze default – masih dipertahankan untuk kompatibilitas) =====
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ===== LEGACY REDIRECT (untuk URL .php lama supaya tidak 404) =====
Route::redirect('/produk-detail.php', '/produk', 301);
Route::redirect('/wishlist.php', '/wishlist', 301);
Route::redirect('/keranjang.php', '/keranjang', 301);
Route::redirect('/checkout.php', '/checkout', 301);
Route::redirect('/akun.php', '/akun', 301);
Route::redirect('/pengaturan.php', '/pengaturan', 301);
Route::redirect('/pesanan.php', '/pesanan', 301);
Route::redirect('/riwayat-pesanan.php', '/riwayat-pesanan', 301);
Route::redirect('/blog.php', '/blog', 301);
Route::redirect('/tentang.php', '/tentang', 301);
Route::redirect('/koleksi.php', '/paket-bundling', 301);
Route::redirect('/koleksi-detail.php', '/paket-bundling', 301);
Route::redirect('/koleksi', '/paket-bundling', 301);
Route::get('/koleksi/{slug}', function ($slug) {
    return redirect('/paket-bundling/' . $slug, 301);
});
Route::redirect('/syarat-ketentuan.php', '/syarat-ketentuan', 301);
Route::redirect('/kebijakan-privasi.php', '/kebijakan-privasi', 301);
Route::redirect('/payment-sukses.php', '/payment-sukses', 301);

require __DIR__.'/auth.php';