<x-app-layout>
    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Pesan Pelanggan (Admin Panel)</h2>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden grid grid-cols-1 md:grid-cols-3 min-h-[600px]">
            
            <!-- Sidebar Daftar Pelanggan -->
            <div class="border-r border-gray-200 bg-gray-50 overflow-y-auto">
                <div class="p-4 border-b border-gray-200 bg-white font-semibold text-gray-700">Daftar Percakapan</div>
                <div class="divide-y divide-gray-100">
                    @forelse($customers as $c)
                        <a href="{{ route('owner.chat.show', $c->id) }}" class="block p-4 hover:bg-gray-100 transition {{ isset($customer) && $customer->id == $c->id ? 'bg-pink-50 border-l-4 border-pink-500' : '' }}">
                            <div class="font-medium text-gray-900">{{ $c->name }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ $c->email }}</div>
                        </a>
                    @empty
                        <div class="p-4 text-sm text-gray-500 text-center">Belum ada pesan masuk.</div>
                    @endforelse
                </div>
            </div>

            <!-- Ruang Chat Aktif (Split-Screen Kanan) -->
            <div class="col-span-2 flex flex-col bg-slate-50 justify-between">
                @isset($customer)
                    <!-- Header Chat -->
                    <div class="p-4 bg-white border-b border-gray-200 flex items-center justify-between shadow-xs">
                        <div>
                            <h3 class="font-bold text-gray-800">{{ $customer->name }}</h3>
                            <span class="text-xs text-gray-500">{{ $customer->email }}</span>
                        </div>
                        <span class="text-xs bg-pink-100 text-pink-700 px-2.5 py-1 rounded-full font-medium">Pelanggan Yessi Komfi</span>
                    </div>

                    <!-- Area Bubble Pesan -->
                    <div class="p-6 overflow-y-auto space-y-4 flex-1 max-h-[480px]">
                        @foreach($messages as $msg)
                            <div class="flex {{ $msg->sender_type == 'admin' ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[70%] rounded-2xl px-4 py-3 text-sm shadow-sm {{ $msg->sender_type == 'admin' ? 'bg-pink-600 text-white rounded-br-none' : 'bg-white text-gray-800 border border-gray-200 rounded-bl-none' }}">
                                    @if($msg->is_bot)
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-pink-200 mb-1">🤖 Shopee Bot Auto-Reply</div>
                                    @endif
                                    <p class="whitespace-pre-line">{{ $msg->message }}</p>
                                    <span class="block text-[10px] mt-1 text-right {{ $msg->sender_type == 'admin' ? 'text-pink-200' : 'text-gray-400' }}">
                                        {{ $msg->created_at->format('H:i') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Input Balasan Admin -->
                    <form action="{{ route('owner.chat.store', $customer->id) }}" method="POST" class="p-4 bg-white border-t border-gray-200 flex gap-3">
                        @csrf
                        <input type="text" name="message" placeholder="Ketik balasan untuk pelanggan..." class="flex-1 border-gray-300 rounded-lg focus:border-pink-500 focus:ring-pink-500 text-sm" required autocomplete="off">
                        <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white font-medium px-6 py-2 rounded-lg text-sm transition shadow-xs">
                            Kirim
                        </button>
                    </form>
                @else
                    <div class="flex items-center justify-center h-full text-gray-400 text-sm">
                        Pilih salah satu pelanggan di sebelah kiri untuk membuka ruang chat.
                    </div>
                @endisset
            </div>

        </div>
    </div>
</x-app-layout>