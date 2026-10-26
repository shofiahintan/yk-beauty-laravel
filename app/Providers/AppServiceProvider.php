<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Data notifikasi bell di topbar Owner Panel — dishare ke semua halaman owner.*
        // lewat composer biar tiap controller gak perlu query ulang manual.
        \Illuminate\Support\Facades\View::composer('layouts.owner', function ($view) {
            if (! auth()->check() || ! auth()->user()->isOwner()) {
                return;
            }

            $notifs = \App\Support\OwnerNotifications::all(auth()->user());

            $view->with('ownerNotifs', $notifs->take(6));
            $view->with('ownerNotifCount', \App\Support\OwnerNotifications::unreadCount($notifs));

            // Badge "Pesan" di sidebar — jumlah pesan pelanggan yang belum dibuka Owner.
            $view->with('ownerChatUnread', \App\Support\ChatService::unreadForOwner());

            // Badge "Refund" di sidebar — jumlah pengajuan refund yang butuh persetujuan.
            $view->with('ownerPendingRefunds', \App\Models\Refund::where('status', \App\Models\Refund::STATUS_DIAJUKAN)->count());
        });
    }
}
