<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OwnerBlogFase5Test extends TestCase
{
    use RefreshDatabase;

    private function createOwner(): User
    {
        return User::factory()->create([
            'role'      => User::ROLE_OWNER,
            'is_active' => true,
        ]);
    }

    private function createCustomer(): User
    {
        return User::factory()->create([
            'role'      => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
    }

    public function test_customer_and_guest_are_denied_access_to_owner_artikel(): void
    {
        // Tamu dialihkan ke login
        $this->get('/owner/artikel')->assertRedirect('/login');
        $this->post('/owner/artikel', [])->assertRedirect('/login');

        // Customer mendapat 404 (stealth security pada panel owner)
        $customer = $this->createCustomer();
        $this->actingAs($customer)->get('/owner/artikel')->assertStatus(404);
        $this->actingAs($customer)->post('/owner/artikel', [])->assertStatus(404);
    }

    public function test_owner_can_view_artikel_index(): void
    {
        $owner = $this->createOwner();

        BlogPost::create([
            'title'        => 'Tips Cantik Alami',
            'slug'         => 'tips-cantik-alami',
            'category'     => 'Tips & Trik',
            'published_at' => '2026-10-20',
            'image'        => 'assets/images/landing3.jpg',
            'excerpt'      => 'Ringkasan tips cantik...',
            'body'         => ['Paragraf 1', 'Paragraf 2'],
        ]);

        $response = $this->actingAs($owner)->get('/owner/artikel');

        $response->assertOk()
            ->assertSee('Tips Cantik Alami')
            ->assertSee('Tips &amp; Trik', false)
            ->assertSee('+ Tambah Artikel');
    }

    public function test_owner_can_create_artikel_with_auto_slug_and_image(): void
    {
        $owner = $this->createOwner();

        $image = UploadedFile::fake()->image('banner.jpg', 600, 400)->size(500);

        $bodyText = "Paragraf pertama artikel kecantikan.\n\nParagraf kedua setelah baris kosong.\n\nParagraf ketiga penutup.";

        $response = $this->actingAs($owner)->post('/owner/artikel', [
            'title'        => 'Rahasia Kulit Bersinar',
            'category'     => 'Skincare Routine',
            'published_at' => '2026-10-25',
            'image'        => $image,
            'excerpt'      => 'Ini adalah ringkasan artikel rahasia kulit.',
            'body'         => $bodyText,
        ]);

        $response->assertRedirect('/owner/artikel')
            ->assertSessionHas('status');

        $post = BlogPost::where('slug', 'rahasia-kulit-bersinar')->first();
        $this->assertNotNull($post);
        $this->assertEquals('Rahasia Kulit Bersinar', $post->title);
        $this->assertEquals('Skincare Routine', $post->category);
        $this->assertCount(3, $post->body);
        $this->assertEquals('Paragraf pertama artikel kecantikan.', $post->body[0]);
        $this->assertEquals('Paragraf kedua setelah baris kosong.', $post->body[1]);
        $this->assertEquals('Paragraf ketiga penutup.', $post->body[2]);

        $this->assertFileExists(public_path($post->image));

        // Cleanup test uploaded file
        if (File::exists(public_path($post->image))) {
            File::delete(public_path($post->image));
        }
    }

    public function test_duplicate_titles_generate_unique_slugs(): void
    {
        $owner = $this->createOwner();

        $image1 = UploadedFile::fake()->image('b1.jpg', 400, 400)->size(100);
        $image2 = UploadedFile::fake()->image('b2.jpg', 400, 400)->size(100);

        // Artikel 1
        $this->actingAs($owner)->post('/owner/artikel', [
            'title'        => 'Panduan Skincare',
            'category'     => 'Tips',
            'published_at' => '2026-10-25',
            'image'        => $image1,
            'excerpt'      => 'Ringkasan 1',
            'body'         => 'Isi artikel 1',
        ])->assertRedirect('/owner/artikel');

        // Artikel 2 dengan judul yang persis sama
        $this->actingAs($owner)->post('/owner/artikel', [
            'title'        => 'Panduan Skincare',
            'category'     => 'Tips',
            'published_at' => '2026-10-25',
            'image'        => $image2,
            'excerpt'      => 'Ringkasan 2',
            'body'         => 'Isi artikel 2',
        ])->assertRedirect('/owner/artikel');

        $post1 = BlogPost::where('slug', 'panduan-skincare')->first();
        $post2 = BlogPost::where('slug', 'panduan-skincare-2')->first();

        $this->assertNotNull($post1);
        $this->assertNotNull($post2);
        $this->assertNotEquals($post1->id, $post2->id);

        // Cleanup
        if (File::exists(public_path($post1->image))) File::delete(public_path($post1->image));
        if (File::exists(public_path($post2->image))) File::delete(public_path($post2->image));
    }

    public function test_owner_can_update_artikel_and_replace_image(): void
    {
        $owner = $this->createOwner();

        $initialImage = UploadedFile::fake()->image('initial.jpg', 400, 400)->size(100);

        $this->actingAs($owner)->post('/owner/artikel', [
            'title'        => 'Artikel Awal',
            'category'     => 'Category A',
            'published_at' => '2026-10-20',
            'image'        => $initialImage,
            'excerpt'      => 'Ringkasan awal',
            'body'         => "Paragraf satu\n\nParagraf dua",
        ]);

        $post = BlogPost::where('slug', 'artikel-awal')->first();
        $oldImagePath = public_path($post->image);
        $this->assertFileExists($oldImagePath);

        // Update dengan judul baru dan gambar pengganti baru
        $newImage = UploadedFile::fake()->image('replacement.webp', 400, 400)->size(100);

        $response = $this->actingAs($owner)->put('/owner/artikel/' . $post->id, [
            'title'        => 'Artikel Baru Diubah',
            'category'     => 'Category B',
            'published_at' => '2026-10-26',
            'image'        => $newImage,
            'excerpt'      => 'Ringkasan setelah update',
            'body'         => "Paragraf revisi 1\n\nParagraf revisi 2\n\nParagraf revisi 3",
        ]);

        $response->assertRedirect('/owner/artikel');

        $post->refresh();
        $this->assertEquals('Artikel Baru Diubah', $post->title);
        $this->assertEquals('artikel-baru-diubah', $post->slug);
        $this->assertEquals('Category B', $post->category);
        $this->assertEquals('2026-10-26', $post->published_at->format('Y-m-d'));
        $this->assertCount(3, $post->body);

        // Gambar lama terhapus dan gambar baru ada
        $this->assertFileDoesNotExist($oldImagePath);
        $this->assertFileExists(public_path($post->image));

        // Cleanup
        if (File::exists(public_path($post->image))) File::delete(public_path($post->image));
    }

    public function test_owner_can_delete_artikel_and_uploaded_image_is_deleted(): void
    {
        $owner = $this->createOwner();

        $fakeImage = UploadedFile::fake()->image('to_delete.png', 400, 400)->size(100);

        $this->actingAs($owner)->post('/owner/artikel', [
            'title'        => 'Artikel Hapus',
            'category'     => 'Umum',
            'published_at' => '2026-10-24',
            'image'        => $fakeImage,
            'excerpt'      => 'Akan dihapus',
            'body'         => 'Isi konten',
        ]);

        $post = BlogPost::where('slug', 'artikel-hapus')->first();
        $imagePath = public_path($post->image);
        $this->assertFileExists($imagePath);

        $response = $this->actingAs($owner)->delete('/owner/artikel/' . $post->id);
        $response->assertRedirect('/owner/artikel')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
        $this->assertFileDoesNotExist($imagePath);
    }

    public function test_deleting_seed_article_does_not_delete_assets_image(): void
    {
        $owner = $this->createOwner();

        // Artikel dengan path seed (dimulai dengan assets/)
        $post = BlogPost::create([
            'title'        => 'Artikel Seed',
            'slug'         => 'artikel-seed',
            'category'     => 'Seed Cat',
            'published_at' => '2024-10-01',
            'image'        => 'assets/images/landing3.jpg',
            'excerpt'      => 'Dari seeder',
            'body'         => ['Paragraf 1'],
        ]);

        $this->actingAs($owner)->delete('/owner/artikel/' . $post->id)
            ->assertRedirect('/owner/artikel');

        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
        // File seed di assets/images/landing3.jpg tidak boleh terhapus
        $this->assertFileExists(public_path('assets/images/landing3.jpg'));
    }

    public function test_validation_rejects_invalid_inputs_and_oversized_images(): void
    {
        $owner = $this->createOwner();

        // 1. Gambar lebih dari 2 MB (2500 KB) ditolak
        $oversizedImage = UploadedFile::fake()->create('big.jpg', 2500, 'image/jpeg');
        $this->actingAs($owner)->post('/owner/artikel', [
            'title'        => 'Judul Valid',
            'category'     => 'Kategori',
            'published_at' => '2026-10-25',
            'image'        => $oversizedImage,
            'excerpt'      => 'Ringkasan',
            'body'         => 'Isi',
        ])->assertSessionHasErrors('image');

        // 2. Format bukan gambar (pdf) ditolak
        $invalidMime = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $this->actingAs($owner)->post('/owner/artikel', [
            'title'        => 'Judul Valid',
            'category'     => 'Kategori',
            'published_at' => '2026-10-25',
            'image'        => $invalidMime,
            'excerpt'      => 'Ringkasan',
            'body'         => 'Isi',
        ])->assertSessionHasErrors('image');

        // 3. Field wajib kosong ditolak
        $this->actingAs($owner)->post('/owner/artikel', [])
            ->assertSessionHasErrors(['title', 'category', 'published_at', 'image', 'excerpt', 'body']);
    }

    public function test_blog_images_display_correctly_on_public_list_detail_and_owner_panel(): void
    {
        $owner = $this->createOwner();

        // 1. Artikel dengan foto seed bawaan
        $seedPost = BlogPost::create([
            'title'        => 'Artikel Bawaan Seeder',
            'slug'         => 'artikel-bawaan-seeder',
            'category'     => 'Tips',
            'published_at' => now()->toDateString(),
            'image'        => 'assets/images/landing3.jpg',
            'excerpt'      => 'Ringkasan artikel seeder',
            'body'         => ['Paragraf konten seeder'],
        ]);

        // 2. Artikel yang diupload owner (tersimpan di assets/images/blog/...)
        $fakeUploaded = UploadedFile::fake()->image('uploaded-article.jpg', 600, 400);
        $this->actingAs($owner)->post('/owner/artikel', [
            'title'        => 'Artikel Baru Diupload',
            'category'     => 'Skincare',
            'published_at' => now()->toDateString(),
            'image'        => $fakeUploaded,
            'excerpt'      => 'Ringkasan artikel baru upload',
            'body'         => 'Isi artikel baru yang diupload.',
        ])->assertRedirect('/owner/artikel');

        $uploadedPost = BlogPost::where('slug', 'artikel-baru-diupload')->first();
        $this->assertNotNull($uploadedPost);
        $this->assertStringContainsString('assets/images/blog/', $uploadedPost->image);
        $this->assertFileExists(public_path($uploadedPost->image));

        // 3. Artikel dengan path uploads/blog/... (format disk public)
        $publicDiskPost = BlogPost::create([
            'title'        => 'Artikel Format Disk Public',
            'slug'         => 'artikel-format-disk-public',
            'category'     => 'Edukasi',
            'published_at' => now()->toDateString(),
            'image'        => 'uploads/blog/custom_public_disk.jpg',
            'excerpt'      => 'Ringkasan format storage',
            'body'         => ['Paragraf storage public'],
        ]);

        // Cek halaman publik daftar blog (/blog)
        $resPublicList = $this->get('/blog');
        $resPublicList->assertOk();
        $resPublicList->assertSee(asset('assets/images/landing3.jpg'), false);
        $resPublicList->assertSee(asset($uploadedPost->image), false);
        $resPublicList->assertSee(asset('storage/uploads/blog/custom_public_disk.jpg'), false);

        // Cek halaman publik detail artikel (/blog/{slug})
        $resDetailSeed = $this->get('/blog/' . $seedPost->slug);
        $resDetailSeed->assertOk();
        $resDetailSeed->assertSee(asset('assets/images/landing3.jpg'), false);

        $resDetailUploaded = $this->get('/blog/' . $uploadedPost->slug);
        $resDetailUploaded->assertOk();
        $resDetailUploaded->assertSee(asset($uploadedPost->image), false);

        $resDetailPublicDisk = $this->get('/blog/' . $publicDiskPost->slug);
        $resDetailPublicDisk->assertOk();
        $resDetailPublicDisk->assertSee(asset('storage/uploads/blog/custom_public_disk.jpg'), false);

        // Cek panel owner (/owner/artikel)
        $resOwnerIndex = $this->actingAs($owner)->get('/owner/artikel');
        $resOwnerIndex->assertOk();
        $resOwnerIndex->assertSee(asset('assets/images/landing3.jpg'), false);
        $resOwnerIndex->assertSee(asset($uploadedPost->image), false);
        $resOwnerIndex->assertSee(asset('storage/uploads/blog/custom_public_disk.jpg'), false);

        // Bersihkan file yang diupload saat tes
        if (File::exists(public_path($uploadedPost->image))) {
            File::delete(public_path($uploadedPost->image));
        }
    }
}
