<x-app-layout>
    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-[550px]">
            <!-- Header Chat -->
            <div class="bg-pink-600 text-white p-4 font-semibold flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <span>💬 Bantuan Pelanggan - Yessi Komfi</span>
                </div>
                <span class="text-xs bg-pink-700 px-2.5 py-1 rounded-full font-medium">Online (Bot Aktif)</span>
            </div>

            <!-- Area Pesan (Bubble Chat) -->
            <div class="flex-1 p-6 overflow-y-auto space-y-4 bg-slate-50">
                @foreach($messages as $msg)
                    <div class="flex {{ $msg->sender_type == 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[75%] rounded-2xl px-4 py-3 text-sm shadow-sm {{ $msg->sender_type == 'user' ? 'bg-pink-600 text-white rounded-br-none' : 'bg-white text-gray-800 border border-gray-200 rounded-bl-none' }}">
                            @if($msg->is_bot)
                                <div class="text-[10px] font-bold uppercase tracking-wider text-pink-600 mb-1">🤖 Bot Yessi Komfi</div>
                            @endif
                            <p class="whitespace-pre-line">{{ $msg->message }}</p>
                            <span class="block text-[10px] mt-1 text-right {{ $msg->sender_type == 'user' ? 'text-pink-200' : 'text-gray-400' }}">
                                {{ $msg->created_at->format('H:i') }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Form Kirim Pesan -->
            <form action="{{ route('chat.store') }}" method="POST" class="p-4 bg-white border-t border-gray-200 flex gap-3">
                @csrf
                <input type="text" name="message" placeholder="Tulis pesan... (Ketik 'Jam' atau 'Kirim' untuk tes Bot)" class="flex-1 border-gray-300 rounded-lg focus:border-pink-500 focus:ring-pink-500 text-sm" required autocomplete="off">
                <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white font-medium px-6 py-2 rounded-lg text-sm transition shadow-xs">
                    Kirim
                </button>
            </form>
        </div>
    </div>
</x-app-layout>