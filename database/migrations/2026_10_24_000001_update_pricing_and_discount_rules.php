<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom harga_normal dan diskon_persen pada tabel products
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('harga_normal')->nullable()->after('harga');
            $table->unsignedTinyInteger('diskon_persen')->nullable()->after('harga_normal');
        });

        // 2. Migrasi data lama products
        $products = DB::table('products')->get();
        foreach ($products as $p) {
            $oldCoret = $p->harga_coret ?? null;
            $harga = (int) $p->harga;

            if ($oldCoret !== null && $oldCoret > $harga) {
                $diskon = (int) round((1 - ($harga / $oldCoret)) * 100);
                DB::table('products')->where('id', $p->id)->update([
                    'harga_normal' => $oldCoret,
                    'diskon_persen' => $diskon,
                ]);
            } else {
                DB::table('products')->where('id', $p->id)->update([
                    'harga_normal' => $harga,
                    'diskon_persen' => null,
                ]);
            }
        }

        // 3. Drop kolom harga_coret dari products
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('harga_coret');
        });

        // 4. Drop kolom harga_coret dari collections
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('harga_coret');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->unsignedInteger('harga_coret')->nullable()->after('harga');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('harga_coret')->nullable()->after('harga');
        });

        $products = DB::table('products')->get();
        foreach ($products as $p) {
            if ($p->diskon_persen && $p->diskon_persen > 0) {
                DB::table('products')->where('id', $p->id)->update([
                    'harga_coret' => $p->harga_normal,
                ]);
            }
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['harga_normal', 'diskon_persen']);
        });
    }
};
