<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ===== CHAT PEMBELI–OWNER =====
// Ruang chat milik owner: notifikasi "ada pesan masuk" — cuma Owner.
Broadcast::channel('chat.owner', function ($user) {
    return $user->isOwner();
});

// Ruang chat satu pelanggan: pelanggan itu sendiri, atau Owner (buat membuka thread-nya).
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    if (! ctype_digit((string) $userId)) {
        return false;
    }

    return $user->isOwner() || (int) $user->id === (int) $userId;
});
