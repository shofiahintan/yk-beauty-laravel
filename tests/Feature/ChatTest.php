<?php

namespace Tests\Feature;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Foto chat disimpan di disk "local" — pakai disk palsu supaya storage/app asli tidak tersentuh.
        Storage::fake('local');
        // Jangan sampai test menembak Pusher sungguhan.
        Event::fake([ChatMessageSent::class]);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    }

    private function owner(): User
    {
        return User::factory()->create(['role' => User::ROLE_OWNER]);
    }

    private function say(User $customer, string $sender, string $body, bool $read = false): ChatMessage
    {
        return ChatMessage::create([
            'user_id' => $customer->id,
            'sender' => $sender,
            'body' => $body,
            'read_at' => $read ? now() : null,
        ]);
    }

    /** @test */
    public function tamu_tidak_bisa_memakai_chat()
    {
        $this->getJson('/chat/messages')->assertUnauthorized();
        $this->postJson('/chat/messages', ['body' => 'halo'])->assertUnauthorized();
    }

    /** @test */
    public function pelanggan_bisa_mengirim_pesan_teks()
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->postJson('/chat/messages', ['body' => 'Halo, stok serum masih ada?'])
            ->assertCreated()
            ->assertJsonPath('message.sender', 'customer')
            ->assertJsonPath('message.body', 'Halo, stok serum masih ada?');

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $customer->id,
            'sender' => 'customer',
            'body' => 'Halo, stok serum masih ada?',
        ]);

        Event::assertDispatched(ChatMessageSent::class);
    }

    /** @test */
    public function pesan_kosong_ditolak()
    {
        $this->actingAs($this->customer())
            ->postJson('/chat/messages', ['body' => '   '])
            ->assertStatus(422);

        $this->assertDatabaseCount('chat_messages', 0);
    }

    /** @test */
    public function pelanggan_hanya_melihat_pesan_miliknya_sendiri()
    {
        $a = $this->customer();
        $b = $this->customer();

        $this->say($a, 'customer', 'pesan milik A');
        $this->say($b, 'customer', 'rahasia milik B');

        $this->actingAs($a)
            ->getJson('/chat/messages')
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', 'pesan milik A')
            ->assertJsonMissing(['body' => 'rahasia milik B']);
    }

    /** @test */
    public function membuka_chat_menandai_balasan_owner_terbaca_dan_badge_turun()
    {
        $customer = $this->customer();
        $this->say($customer, 'owner', 'Ready kak, silakan checkout.');

        $this->actingAs($customer)->getJson('/chat/unread')->assertJsonPath('unread', 1);

        // tanpa open=1 (widget tertutup) tetap belum terbaca
        $this->actingAs($customer)->getJson('/chat/messages')->assertJsonPath('unread', 1);

        $this->actingAs($customer)->getJson('/chat/messages?open=1')->assertJsonPath('unread', 0);

        $this->assertNotNull(ChatMessage::first()->read_at);
    }

    /** @test */
    public function polling_dengan_after_hanya_mengembalikan_pesan_yang_lebih_baru()
    {
        $customer = $this->customer();
        $first = $this->say($customer, 'customer', 'satu');
        $this->say($customer, 'owner', 'dua');

        $this->actingAs($customer)
            ->getJson('/chat/messages?after='.$first->id)
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', 'dua');
    }

    /** @test */
    public function owner_tidak_bisa_memakai_endpoint_chat_pelanggan()
    {
        $this->actingAs($this->owner())->getJson('/chat/messages')->assertNotFound();
        $this->actingAs($this->owner())->postJson('/chat/messages', ['body' => 'x'])->assertNotFound();
    }

    /** @test */
    public function pelanggan_tidak_bisa_membuka_halaman_atau_endpoint_owner()
    {
        $customer = $this->customer();
        $other = $this->customer();

        $this->actingAs($customer)->get('/owner/pesan')->assertNotFound();
        $this->actingAs($customer)->getJson('/owner/pesan/'.$other->id.'/messages')->assertNotFound();
        $this->actingAs($customer)->postJson('/owner/pesan/'.$other->id.'/messages', ['body' => 'x'])->assertNotFound();
    }

    /** @test */
    public function owner_melihat_daftar_percakapan_dengan_jumlah_belum_dibaca()
    {
        $a = $this->customer();
        $b = $this->customer();

        $this->say($a, 'customer', 'a1');
        $this->say($a, 'customer', 'a2');
        $this->say($b, 'customer', 'b1');
        $this->say($b, 'owner', 'balasan untuk B');

        $owner = $this->owner();

        $response = $this->actingAs($owner)->getJson('/owner/pesan/threads')->assertOk();

        $response->assertJsonCount(2, 'threads');
        $response->assertJsonPath('unread', 3);          // a1, a2, b1
        $response->assertJsonPath('threads.0.user_id', $b->id); // B punya pesan terakhir → paling atas
        $response->assertJsonPath('threads.0.unread', 1);
        $response->assertJsonPath('threads.1.unread', 2);

        $this->actingAs($owner)->get('/owner/pesan')->assertOk();
        $this->actingAs($owner)->get('/owner/pesan/'.$a->id)->assertOk();
    }

    /** @test */
    public function membuka_thread_menandai_pesan_pelanggan_terbaca_untuk_owner()
    {
        $customer = $this->customer();
        $this->say($customer, 'customer', 'halo');

        $owner = $this->owner();

        $this->actingAs($owner)
            ->getJson('/owner/pesan/'.$customer->id.'/messages?open=1')
            ->assertOk()
            ->assertJsonPath('unread', 0);

        $this->assertSame(0, ChatMessage::unreadByOwner()->count());
    }

    /** @test */
    public function owner_bisa_membalas_dan_pesan_tercatat_di_ruang_pelanggan_itu()
    {
        $customer = $this->customer();

        $this->actingAs($this->owner())
            ->postJson('/owner/pesan/'.$customer->id.'/messages', ['body' => 'Baik kak, segera kami kirim.'])
            ->assertCreated()
            ->assertJsonPath('message.sender', 'owner');

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $customer->id,
            'sender' => 'owner',
            'body' => 'Baik kak, segera kami kirim.',
        ]);
    }

    /** @test */
    public function ruang_chat_tidak_bisa_dibuka_untuk_akun_owner()
    {
        $owner = $this->owner();
        $otherOwner = $this->owner();

        $this->actingAs($owner)->get('/owner/pesan/'.$otherOwner->id)->assertNotFound();
    }

    /** @test */
    public function pelanggan_bisa_mengirim_foto_dan_file_tersimpan_di_disk_privat()
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->post('/chat/messages', ['image' => UploadedFile::fake()->image('bukti-transfer.jpg', 600, 400)], ['Accept' => 'application/json'])
            ->assertCreated();

        $message = ChatMessage::first();

        $this->assertNotNull($message->image_path);
        Storage::disk('local')->assertExists($message->image_path);
    }

    /** @test */
    public function file_yang_bukan_gambar_ditolak()
    {
        $this->actingAs($this->customer())
            ->post('/chat/messages', ['image' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');

        $this->assertDatabaseCount('chat_messages', 0);
    }

    /** @test */
    public function gambar_lebih_dari_5mb_ditolak()
    {
        $this->actingAs($this->customer())
            ->post('/chat/messages', ['image' => UploadedFile::fake()->image('besar.jpg')->size(6000)], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');

        $this->assertDatabaseCount('chat_messages', 0);
    }

    /** @test */
    public function foto_chat_hanya_bisa_dilihat_pemilik_ruang_dan_owner()
    {
        $a = $this->customer();
        $b = $this->customer();

        $this->actingAs($a)->post('/chat/messages', ['image' => UploadedFile::fake()->image('foto.png')], ['Accept' => 'application/json'])->assertCreated();
        $message = ChatMessage::first();
        $url = '/chat/image/'.$message->id;

        $this->actingAs($a)->get($url)->assertOk();
        $this->actingAs($this->owner())->get($url)->assertOk();
        $this->actingAs($b)->get($url)->assertNotFound();
    }

    /** @test */
    public function tamu_tidak_bisa_membuka_foto_chat()
    {
        $customer = $this->customer();
        $message = ChatMessage::create([
            'user_id' => $customer->id,
            'sender' => 'customer',
            'image_path' => 'chat/contoh.jpg',
        ]);

        $this->get('/chat/image/'.$message->id)->assertRedirect('/login');
    }
}
