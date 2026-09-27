{{-- Widget chat pembeli–owner. Hanya untuk pelanggan yang sudah login (owner punya panel sendiri di /owner/pesan). --}}
@auth
@unless (auth()->user()->isOwner())
@php
    $ykChatUnread = \App\Models\ChatMessage::where('user_id', auth()->id())->unreadByCustomer()->count();
    $ykChatPusher = \App\Support\ChatService::pusherConfig();
@endphp

<link rel="stylesheet" href="{{ asset('css/chat.css') }}">

<div id="ykChat" class="yk-chat">
  <button type="button" class="yk-chat-launcher" aria-label="{{ __('chat_open') }}" aria-expanded="false" aria-controls="ykChatPanel">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><circle cx="8" cy="10" r="1" fill="currentColor" stroke="none"/><circle cx="12" cy="10" r="1" fill="currentColor" stroke="none"/><circle cx="16" cy="10" r="1" fill="currentColor" stroke="none"/></svg>
    <span class="yk-chat-badge">0</span>
  </button>

  <section class="yk-chat-panel" id="ykChatPanel" role="dialog" aria-label="{{ __('chat_title') }}">
    <header class="yk-chat-head">
      <div class="yk-chat-avatar">YK</div>
      <div class="yk-chat-head-text">
        <strong>{{ __('chat_title') }}</strong>
        <span>{{ __('chat_subtitle') }}</span>
      </div>
      <button type="button" class="yk-chat-close" aria-label="{{ __('chat_close') }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </header>

    <div class="yk-chat-scroll" id="ykChatBox"></div>

    <form class="yk-chat-form" id="ykChatForm" novalidate>
      <div class="yk-chat-preview" id="ykChatPreview">
        <img src="" alt="">
        <button type="button" class="yk-chat-preview-x" aria-label="{{ __('chat_remove_photo') }}">&times;</button>
      </div>
      <p class="yk-chat-error" id="ykChatError" role="alert"></p>
      <div class="yk-chat-row">
        <button type="button" class="yk-chat-attach" id="ykChatAttach" aria-label="{{ __('chat_attach') }}" title="{{ __('chat_attach') }}">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-8 9"/></svg>
        </button>
        <input type="file" id="ykChatFile" accept="image/jpeg,image/png,image/webp" hidden>
        <textarea id="ykChatText" rows="1" maxlength="2000" placeholder="{{ __('chat_placeholder') }}"></textarea>
        <button type="submit" class="yk-chat-send" id="ykChatSend" aria-label="{{ __('chat_send') }}" title="{{ __('chat_send') }}">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7Z"/></svg>
        </button>
      </div>
    </form>
  </section>
</div>

@php
$ykChatConfig = [
    'listUrl' => route('chat.index'),
    'sendUrl' => route('chat.store'),
    'unreadUrl' => route('chat.unread'),
    'userId' => auth()->id(),
    'unread' => $ykChatUnread,
    'pusher' => $ykChatPusher,
    'i18n' => [
        'locale' => app()->getLocale() === 'en' ? 'en-US' : 'id-ID',
        'today' => __('chat_today'),
        'yesterday' => __('chat_yesterday'),
        'older' => __('chat_older'),
        'empty' => __('chat_empty'),
        'botLabel' => __('chat_bot_label'),
        'errSend' => __('chat_err_send'),
        'errType' => __('chat_err_type'),
        'errSize' => __('chat_err_size'),
        'errSession' => __('chat_err_session'),
        'errEmpty' => __('chat_err_empty'),
    ],
];
@endphp
<script>
window.YK_CHAT = @json($ykChatConfig);
</script>
<script src="{{ asset('js/chat.js') }}"></script>
@endunless
@endauth