<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use App\Support\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Chat sisi pelanggan (widget di storefront). Satu ruang chat per pelanggan,
 * jadi pelanggan tidak pernah mengirim ID ruang — selalu pakai akun yang sedang login.
 */
class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->customer($request);

        return $this->noStore(ChatService::fetch($user->id, $request, ChatMessage::SENDER_CUSTOMER));
    }

    public function store(Request $request)
    {
        $user = $this->customer($request);

        $message = ChatService::store($user->id, $request, ChatMessage::SENDER_CUSTOMER);

        return response()->json(['message' => $message->toChatArray()], 201);
    }

    public function unread(Request $request)
    {
        $user = $this->customer($request);

        return $this->noStore([
            'unread' => ChatService::unreadCount($user->id, ChatMessage::SENDER_CUSTOMER),
        ]);
    }

    /**
     * Foto chat — hanya boleh dilihat pelanggan pemilik ruang chat-nya dan Owner.
     * (Bukti transfer / foto produk rusak tidak boleh bisa ditebak lewat URL publik.)
     */
    public function image(Request $request, ChatMessage $message)
    {
        $user = $request->user();

        abort_unless($user->isOwner() || (int) $message->user_id === (int) $user->id, 404);
        abort_unless($message->image_path && Storage::disk('local')->exists($message->image_path), 404);

        return Storage::disk('local')->response($message->image_path, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Owner tidak punya ruang chat sendiri — pakai panel /owner/pesan. */
    private function customer(Request $request): User
    {
        $user = $request->user();

        abort_if($user->isOwner(), 404);

        return $user;
    }

    private function noStore(array $data)
    {
        return response()->json($data)->header('Cache-Control', 'no-store');
    }
}
