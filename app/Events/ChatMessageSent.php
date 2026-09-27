<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Pesan chat baru.
 *
 *  - private-chat.{userId} : ruang chat pelanggan tsb (didengar pelanggan itu + Owner yang sedang membukanya)
 *  - private-chat.owner    : hanya kalau pengirimnya pelanggan — supaya badge "Pesan" di panel Owner
 *                            ikut naik walau Owner sedang di halaman lain
 *
 * ShouldBroadcastNow = langsung dikirim di request yang sama (tanpa queue worker).
 */
class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public ChatMessage $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn()
    {
        $channels = [new PrivateChannel('chat.'.$this->message->user_id)];

        if ($this->message->sender === ChatMessage::SENDER_CUSTOMER) {
            $channels[] = new PrivateChannel('chat.owner');
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith()
    {
        return ['message' => $this->message->toChatArray()];
    }
}
