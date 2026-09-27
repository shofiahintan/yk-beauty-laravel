<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $isLocal = app()->environment('local');

        if ($isLocal) {
            $this->call([
                ProductSeeder::class,
                CollectionSeeder::class,
                BlogPostSeeder::class,
                OrderSeeder::class,
            ]);

            \App\Models\User::firstOrCreate(
                ['email' => 'user@ykbeauty.com'],
                [
                    'name' => 'Pelanggan YK Beauty',
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                    'whatsapp' => '083848835777',
                    'alamat' => 'Pakisaji, Kabupaten Malang, Jawa Timur',
                ]
            );
        }

        $ownerPassword = env('SEED_OWNER_PASSWORD');
        if (! $ownerPassword && $isLocal) {
            $ownerPassword = 'password';
        }

        if ($ownerPassword) {
            // Akun Owner default — login lewat form yang sama di /login, redirect otomatis ke /owner/dashboard
            \App\Models\User::firstOrCreate(
                ['email' => 'owner@ykbeauty.com'],
                [
                    'name' => 'Nama Owner',
                    'role' => \App\Models\User::ROLE_OWNER,
                    'password' => \Illuminate\Support\Facades\Hash::make($ownerPassword),
                    'whatsapp' => '081234567890',
                ]
            );
        }
    }
}
