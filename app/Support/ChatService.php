<?php

namespace App\Support;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Logika chat yang dipakai bersama oleh sisi pelanggan (ChatController)
 * dan sisi Owner (Owner\ChatController), supaya aturannya cuma ada satu.
 */
class ChatService
{
    public const PAGE_SIZE = 40;
    public const MAX_IMAGE_KB = 5120; // 5 MB

    /**
     * Bot FAQ otomatis (ala fitur "Balasan Cepat" toko online): begitu pelanggan chat,
     * bot langsung menjawab kalau pertanyaannya cocok salah satu topik umum di bawah ini.
     * Bot berhenti otomatis begitu Owner (manusia) membalas sendiri di ruang chat itu — lihat hasHumanReplied().
     */
    private const FAQ = [
        [
            'keywords' => ['jam buka', 'jam operasional', 'buka jam', 'tutup jam', 'operasional', 'jam berapa', 'jam kerja', 'senin sampai', 'hari apa saja'],
            'answer' => "Toko kami buka online 24 jam untuk order & chat kak, tapi admin standby fast response jam 08.00–21.00 WIB setiap hari. Di luar jam itu boleh tetap chat ya, nanti dibalas begitu admin online lagi.",
        ],
        [
            'keywords' => ['cara order', 'cara pesan', 'cara beli', 'gimana order', 'gimana pesan', 'cara checkout', 'cara belanja', 'mau order', 'mau beli'],
            'answer' => "Gampang kak: pilih produk → masuk Keranjang → Checkout, isi data & alamat, lalu pilih metode pembayaran. Setelah bayar, pesanan otomatis masuk ke sistem kami dan langsung kami proses.",
        ],
        [
            'keywords' => ['ongkir', 'ongkos kirim', 'pengiriman', 'kirim ke', 'estimasi sampai', 'berapa lama sampai', 'ekspedisi', 'kapan sampai', 'lama pengiriman'],
            'answer' => "Ongkir dihitung otomatis saat checkout sesuai alamat & ekspedisi yang kakak pilih (JNE/J&T/SiCepat, dll). Estimasi sampai biasanya 1–4 hari tergantung lokasi.",
        ],
        [
            'keywords' => ['bisa cod', 'ada cod', 'cod tidak', 'cod gak', 'bayar ditempat', 'bayar di tempat'],
            'answer' => "Saat ini kami belum melayani COD kak, pembayaran hanya lewat transfer bank, QRIS, atau e-wallet saat checkout.",
        ],
        [
            'keywords' => ['bayar', 'pembayaran', 'transfer', 'metode bayar', 'qris', 'e-wallet', 'ewallet', 'ovo', 'dana', 'gopay'],
            'answer' => "Pembayaran bisa lewat transfer bank, QRIS, atau e-wallet — metode yang tersedia muncul otomatis saat checkout kak.",
        ],
        [
            'keywords' => ['retur', 'tukar barang', 'refund', 'garansi', 'barang rusak', 'produk rusak', 'komplain', 'barang cacat', 'salah kirim', 'tidak sesuai'],
            'answer' => "Kalau barang diterima rusak/tidak sesuai, kakak bisa chat di sini maksimal 2x24 jam setelah pesanan diterima beserta foto/video buktinya — nanti kami bantu proses retur/penggantiannya.",
        ],
        [
            'keywords' => ['promo', 'diskon', 'voucher', 'potongan harga', 'flash sale', 'ada promo'],
            'answer' => "Info promo & diskon terbaru selalu kami update di halaman produk kak, jadi jangan lupa dicek ya!",
        ],
        [
            'keywords' => ['produk asli', 'original', 'bpom', 'keaslian', 'ori gak', 'ori ga', 'asli gak', 'asli ga'],
            'answer' => "Semua produk yang kami jual 100% original dan sudah terdaftar BPOM kak, aman dipakai.",
        ],
        [
            'keywords' => ['reseller', 'dropship', 'agen', 'jadi mitra', 'kerjasama jual', 'mau jualan'],
            'answer' => "Terima kasih sudah tertarik jadi reseller/dropship kak! Untuk info kerjasama & harga khusus, silakan tinggalkan pesan di sini, nanti admin bantu jelaskan lebih lanjut.",
        ],
        [
            'keywords' => ['lacak pesanan', 'cek pesanan', 'nomor resi', 'resi', 'sudah dikirim belum', 'status pesanan'],
            'answer' => "Status & resi pesanan bisa kakak cek langsung di menu \"Pesanan Saya\" pada akun kakak ya, real-time mengikuti status pengiriman.",
        ],
        [
            'keywords' => ['batalkan pesanan', 'batal pesanan', 'cancel pesanan', 'cancel order'],
            'answer' => "Pesanan yang belum diproses masih bisa dibatalkan lewat menu \"Pesanan Saya\". Kalau statusnya sudah diproses/dikirim, chat kami di sini ya kak biar dibantu cek dulu.",
        ],
        [
            'keywords' => ['alamat toko', 'lokasi toko', 'dimana toko', 'kantor dimana'],
            'answer' => "Toko kami di Kec. Pakisaji, Kabupaten Malang, Jawa Timur. Saat ini kami hanya melayani pemesanan online lewat website ya kak, belum ada COD.",
        ],
        [
            'keywords' => ['minimal order', 'minimal belanja', 'min order', 'boleh beli satu'],
            'answer' => "Gak ada minimal order kok kak, mau beli satu item juga boleh banget.",
        ],
    ];

    /**
     * Ambil pesan satu ruang chat.
     *
     *  - ?after=ID   → pesan yang lebih baru dari ID (dipakai polling / sambung ulang)
     *  - ?before=ID  → satu halaman pesan lebih lama (tombol "Muat pesan sebelumnya")
     *  - tanpa param → halaman terbaru
     *  - ?open=1     → pembaca sedang melihat ruang chat ini → pesan dari pihak lawan ditandai terbaca
     *
     * @param  string  $reader  ChatMessage::SENDER_CUSTOMER | SENDER_OWNER (siapa yang membaca)
     */
    public static function fetch(int $customerId, Request $request, string $reader): array
    {
        $after = (int) $request->query('after', 0);
        $before = (int) $request->query('before', 0);

        $query = ChatMessage::where('user_id', $customerId);
        $hasMore = false;

        if ($after > 0) {
            $messages = $query->where('id', '>', $after)->orderBy('id')->limit(200)->get();
        } else {
            if ($before > 0) {
                $query->where('id', '<', $before);
            }

            $messages = $query->orderByDesc('id')->limit(self::PAGE_SIZE + 1)->get();
            $hasMore = $messages->count() > self::PAGE_SIZE;
            $messages = $messages->take(self::PAGE_SIZE)->reverse()->values();
        }

        if ($request->boolean('open')) {
            self::markRead($customerId, $reader);
        }

        return [
            'messages' => $messages->map(function (ChatMessage $m) {
                return $m->toChatArray();
            })->values()->all(),
            'has_more' => $hasMore,
            'unread' => self::unreadCount($customerId, $reader),
        ];
    }

    /**
     * Validasi + simpan pesan baru (teks dan/atau satu foto), lalu broadcast.
     * Foto disimpan di disk "local" (storage/app/chat) — privat, dilayani lewat route chat.image.
     */
    public static function store(int $customerId, Request $request, string $sender): ChatMessage
    {
        $request->validate([
            'body' => ['nullable', 'string', 'max:2000', 'required_without:image'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_IMAGE_KB, 'required_without:body'],
        ], [
            'body.required_without' => __('chat_err_empty'),
            'image.required_without' => __('chat_err_empty'),
            'body.max' => __('chat_err_long'),
            'image.image' => __('chat_err_type'),
            'image.mimes' => __('chat_err_type'),
            'image.file' => __('chat_err_type'),
            'image.max' => __('chat_err_size'),
            'image.uploaded' => __('chat_err_upload'),
        ]);

        $path = $request->hasFile('image')
            ? $request->file('image')->store('chat', 'local')
            : null;

        $message = ChatMessage::create([
            'user_id' => $customerId,
            'sender' => $sender,
            'body' => $request->input('body'),
            'image_path' => $path,
        ]);

        // Pesan sudah tersimpan — kalau Pusher bermasalah (key salah, internet putus),
        // jangan bikin pengiriman gagal. Penerima tetap dapat pesannya lewat polling.
        try {
            event(new ChatMessageSent($message));
        } catch (\Throwable $e) {
            report($e);
        }

        if ($sender === ChatMessage::SENDER_CUSTOMER) {
            self::maybeAutoReply($customerId, $message);
        }

        return $message;
    }

    /**
     * Balas otomatis pesan pelanggan kalau cocok topik FAQ, atau kirim sambutan
     * kalau ini pesan pertama di ruang chat itu. Tidak aktif lagi begitu ada
     * balasan manusia dari Owner (lihat hasHumanReplied()).
     */
    private static function maybeAutoReply(int $customerId, ChatMessage $customerMessage): void
    {
        if (self::hasHumanReplied($customerId)) {
            return;
        }

        $isFirstMessage = ChatMessage::where('user_id', $customerId)->count() === 1;
        $matched = self::matchFaq($customerMessage->body);

        if ($isFirstMessage) {
            $text = "Halo! 👋 Terima kasih sudah menghubungi YK Beauty & Skincare.";
            $text .= $matched
                ? "\n\n".$matched
                : "\n\nAdmin kami akan segera membalas. Sementara menunggu, kakak juga bisa tanya soal: jam operasional, cara order, pengiriman & ongkir, pembayaran, atau retur & garansi.";
        } elseif ($matched) {
            $text = $matched;
        } else {
            return; // bukan pesan pertama & tidak cocok topik apa pun → biarkan admin yang jawab, jangan spam
        }

        $bot = ChatMessage::create([
            'user_id' => $customerId,
            'sender' => ChatMessage::SENDER_OWNER,
            'is_bot' => true,
            'body' => $text,
        ]);

        try {
            event(new ChatMessageSent($bot));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Owner manusia (bukan bot) sudah pernah membalas di ruang chat ini? */
    private static function hasHumanReplied(int $customerId): bool
    {
        return ChatMessage::where('user_id', $customerId)
            ->where('sender', ChatMessage::SENDER_OWNER)
            ->where('is_bot', false)
            ->exists();
    }

    /** Cari jawaban FAQ pertama yang kata kuncinya cocok dengan isi pesan. */
    private static function matchFaq(?string $body): ?string
    {
        if (! $body) {
            return null;
        }

        $body = Str::lower($body);

        foreach (self::FAQ as $topic) {
            foreach ($topic['keywords'] as $keyword) {
                if (Str::contains($body, $keyword)) {
                    return $topic['answer'];
                }
            }
        }

        return null;
    }

    /** Tandai pesan dari pihak lawan sebagai sudah dibaca $reader. */
    public static function markRead(int $customerId, string $reader): void
    {
        $from = $reader === ChatMessage::SENDER_OWNER
            ? ChatMessage::SENDER_CUSTOMER
            : ChatMessage::SENDER_OWNER;

        ChatMessage::where('user_id', $customerId)
            ->where('sender', $from)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public static function unreadCount(int $customerId, string $reader): int
    {
        $from = $reader === ChatMessage::SENDER_OWNER
            ? ChatMessage::SENDER_CUSTOMER
            : ChatMessage::SENDER_OWNER;

        return ChatMessage::where('user_id', $customerId)
            ->where('sender', $from)
            ->whereNull('read_at')
            ->count();
    }

    /** Total pesan pelanggan yang belum dibuka Owner (untuk badge sidebar). */
    public static function unreadForOwner(): int
    {
        return ChatMessage::unreadByOwner()->count();
    }

    /**
     * Daftar percakapan untuk panel Owner: pelanggan yang pernah chat,
     * terbaru di atas, lengkap dengan pratinjau pesan terakhir + jumlah belum dibaca.
     */
    public static function threads(int $limit = 100): array
    {
        $rows = ChatMessage::query()
            ->select('user_id')
            ->selectRaw('MAX(id) as last_id')
            ->selectRaw('SUM(CASE WHEN sender = ? AND read_at IS NULL THEN 1 ELSE 0 END) as unread', [ChatMessage::SENDER_CUSTOMER])
            ->groupBy('user_id')
            ->orderByDesc('last_id')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $last = ChatMessage::whereIn('id', $rows->pluck('last_id'))->get()->keyBy('id');
        $users = User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($last, $users) {
            $message = $last->get($row->last_id);
            $user = $users->get($row->user_id);

            if (! $message || ! $user) {
                return null;
            }

            $preview = ($message->body !== null && $message->body !== '')
                ? Str::limit($message->body, 70)
                : 'Foto';

            $prefix = '';
            if ($message->sender === ChatMessage::SENDER_OWNER && ! $message->is_bot) {
                $prefix = 'Anda: ';
            }

            return [
                'user_id' => (int) $row->user_id,
                'name' => $user->name,
                'preview' => $prefix.$preview,
                'is_bot' => (bool) $message->is_bot,
                'last_at' => optional($message->created_at)->toIso8601String(),
                'unread' => (int) $row->unread,
                'url' => route('owner.pesan.show', $row->user_id),
            ];
        })->filter()->values()->all();
    }

    /**
     * Konfigurasi Pusher untuk browser, atau null kalau real-time belum aktif
     * (BROADCAST_DRIVER bukan pusher / key kosong) — saat null, chat jalan lewat polling.
     * Secret TIDAK pernah dikirim ke browser.
     */
    public static function pusherConfig(): ?array
    {
        if (config('broadcasting.default') !== 'pusher') {
            return null;
        }

        $key = config('broadcasting.connections.pusher.key');

        if (! $key) {
            return null;
        }

        return [
            'key' => $key,
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            'authUrl' => url('/broadcasting/auth'),
        ];
    }
}