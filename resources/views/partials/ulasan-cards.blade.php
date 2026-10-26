@if($realReviews)
  @foreach($realReviews as $review)
    <div class="ulasan-card">
      <span class="stars">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
      <p class="quote">"{{ \Illuminate\Support\Str::limit($review->comment, 180) }}"</p>
      <div class="ulasan-person">
        <div class="avatar">{{ strtoupper(substr($review->user->name ?? '?', 0, 1)) }}</div>
        <div><div class="nama">{{ $review->user->name ?? __('Pembeli') }}</div><div class="status">{{ __('Pembeli Terverifikasi') }}</div></div>
      </div>
    </div>
  @endforeach
@else
  <div class="ulasan-card">
    <span class="stars">★★★★★</span>
    <p class="quote">"{{ __('Produk YK Beauty benar-benar mengubah kulitku. Teksturnya sangat mewah dan hasilnya terlihat dalam seminggu.') }}"</p>
    <div class="ulasan-person">
      <div class="avatar">D</div>
      <div><div class="nama">Dian S.</div><div class="status">{{ __('Pelanggan Setia') }}</div></div>
    </div>
  </div>
  <div class="ulasan-card">
    <span class="stars">★★★★★</span>
    <p class="quote">"{{ __('Radiance Night Cream adalah holy grail! Bangun tidur kulit terasa kenyal dan cerah. Sangat merekomendasikan.') }}"</p>
    <div class="ulasan-person">
      <div class="avatar">A</div>
      <div><div class="nama">Amanda R.</div><div class="status">{{ __('Pembeli Terverifikasi') }}</div></div>
    </div>
  </div>
  <div class="ulasan-card">
    <span class="stars">★★★★☆</span>
    <p class="quote">"{{ __('Pengemasan sangat elegan dan pengiriman cepat. Serum vitamin C nya cepat meresap dan tidak lengket sama sekali.') }}"</p>
    <div class="ulasan-person">
      <div class="avatar">N</div>
      <div><div class="nama">Nadia K.</div><div class="status">{{ __('Pembeli Terverifikasi') }}</div></div>
    </div>
  </div>
  <div class="ulasan-card">
    <span class="stars">★★★★★</span>
    <p class="quote">"{{ __('Body lotion Scarlett wanginya awet seharian, kulit juga jadi lebih cerah dan lembab. Repeat order terus!') }}"</p>
    <div class="ulasan-person">
      <div class="avatar">R</div>
      <div><div class="nama">Rina P.</div><div class="status">{{ __('Pembeli Terverifikasi') }}</div></div>
    </div>
  </div>
  <div class="ulasan-card">
    <span class="stars">★★★★☆</span>
    <p class="quote">"{{ __('Awalnya ragu karena harga terjangkau, ternyata kualitasnya nggak murahan. CS juga fast response banget.') }}"</p>
    <div class="ulasan-person">
      <div class="avatar">B</div>
      <div><div class="nama">Bayu S.</div><div class="status">{{ __('Pelanggan Setia') }}</div></div>
    </div>
  </div>
  <div class="ulasan-card">
    <span class="stars">★★★★★</span>
    <p class="quote">"{{ __('Paket Daily Glowing worth it banget, sekali beli udah lengkap. Kulit terasa beda setelah rutin 2 minggu.') }}"</p>
    <div class="ulasan-person">
      <div class="avatar">S</div>
      <div><div class="nama">Siti A.</div><div class="status">{{ __('Pembeli Terverifikasi') }}</div></div>
    </div>
  </div>
@endif34444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444 