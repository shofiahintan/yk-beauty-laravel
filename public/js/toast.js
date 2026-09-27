/**
 * Toast popup global — muncul di pojok kanan atas.
 * Pakai: ykToast('Pesan kamu', 'success' | 'error')
 */
(function () {
  function ensureStack() {
    let stack = document.getElementById('ykToastStack');
    if (!stack) {
      stack = document.createElement('div');
      stack.id = 'ykToastStack';
      stack.className = 'yk-toast-stack';
      document.body.appendChild(stack);
    }
    return stack;
  }

  window.ykToast = function (message, type) {
    if (!message) return;
    type = type === 'error' ? 'error' : 'success';

    const stack = ensureStack();
    const el = document.createElement('div');
    el.className = `yk-toast yk-toast--${type}`;
    el.innerHTML = `
      <span class="yk-toast-icon">${type === 'error' ? '!' : '✓'}</span>
      <span class="yk-toast-msg"></span>
      <button type="button" class="yk-toast-close" aria-label="Tutup">✕</button>
    `;
    el.querySelector('.yk-toast-msg').textContent = message;

    const remove = () => {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 250);
    };
    el.querySelector('.yk-toast-close').addEventListener('click', remove);

    stack.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(remove, 4000);
  };
})();
