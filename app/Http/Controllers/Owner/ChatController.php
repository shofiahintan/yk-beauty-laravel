<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\User;
use App\Support\ChatService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /** Daftar percakapan (belum ada yang dibuka). */
    public function index()
    {
        return $this->page(null);
    }

    /** Daftar percakapan + satu ruang chat pelanggan terbuka. */
    public function show(User $user)
    {
        $this->assertCustomer($user);

        return $this->page($user);
    }

    /** JSON: daftar percakapan + total belum dibaca (di-refresh oleh JS). */
    public function threads()
    {
        return response()->json([
            'threads' => ChatService::threads(),
            'unread' => ChatService::unreadForOwner(),
        ])->header('Cache-Control', 'no-store');
    }

    /** JSON: total pesan belum dibaca, untuk badge sidebar di halaman Owner lain. */
    public function unread()
    {
        return response()->json(['unread' => ChatService::unreadForOwner()])
            ->header('Cache-Control', 'no-store');
    }

    public function messages(Request $request, User $user)
    {
        $this->assertCustomer($user);

        return response()->json(ChatService::fetch($user->id, $request, ChatMessage::SENDER_OWNER))
            ->header('Cache-Control', 'no-store');
    }

    public function send(Request $request, User $user)
    {
        $this->assertCustomer($user);

        $message = ChatService::store($user->id, $request, ChatMessage::SENDER_OWNER);

        return response()->json(['message' => $message->toChatArray()], 201);
    }

    private function page(?User $active)
    {
        return view('owner.pesan', [
            'threads' => ChatService::threads(),
            'active' => $active,
        ]);
    }

    /** Ruang chat hanya ada untuk pelanggan — akun owner tidak punya. */
    private function assertCustomer(User $user): void
    {
        abort_if($user->isOwner(), 404);
    }
}
