<?php

return [
    // Tarif ongkir flat untuk pengiriman kurir (dalam Rupiah).
    // Dipakai di CheckoutController::store() sebagai satu-satunya sumber kebenaran.
    // Nilai ini juga dikirim ke frontend checkout hanya untuk tampilan.
    'shipping_flat' => (int) env('SHOP_SHIPPING_FLAT', 15000),
];
