{{-- Modal ulasan wajib. Cuma untuk pelanggan yang sudah login (owner tidak punya pesanan). --}}
@auth
@unless (auth()->user()->isOwner())
@php
    $ykPendingReview = \App\Http\Controllers\ReviewController::pendingPayload(
        \App\Http\Controllers\ReviewController::nextPendingFor(auth()->id())
    );
@endphp

<link rel="stylesheet" href="{{ asset('css/review.css') }}">

<script>
window.YK_REVIEW = {
    storeUrl: @json(route('review.store')),
    pending: @json($ykPendingReview),
};
window.YK_REVIEW_I18N = {
    eyebrow: @json(__('review_eyebrow')),
    title: @json(__('review_title')),
    ratingLabel: @json(__('review_rating_label')),
    placeholder: @json(__('review_placeholder')),
    submitBtn: @json(__('review_submit_btn')),
    submitting: @json(__('review_submitting')),
    errRating: @json(__('review_err_rating')),
    errComment: @json(__('review_err_comment')),
    errSubmit: @json(__('review_err_submit')),
};
</script>
<script src="{{ asset('js/review.js') }}"></script>
@endunless
@endauth
