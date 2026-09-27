<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function tentang()
    {
        return view('tentang');
    }

    public function syarat()
    {
        return view('syarat-ketentuan');
    }

    public function privasi()
    {
        return view('kebijakan-privasi');
    }

    public function notFound()
    {
        return response()->view('404', [], 404);
    }
}
