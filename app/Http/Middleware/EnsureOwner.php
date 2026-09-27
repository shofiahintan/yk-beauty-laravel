<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwner
{
    /**
     * Gerbang buat semua route /owner/*. Customer yang nyasar ke sini (misal nebak URL)
     * dilempar balik ke storefront, bukan dikasih 403 mentah — biar gak bocor info panel admin ada.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isOwner()) {
            abort(404);
        }

        return $next($request);
    }
}
