// ===== Pesanan — pengelolaan data pesanan (database via OrderLifecycle dengan fallback localStorage) =====
// Catatan: Pesanan tersimpan di database dan dikelola oleh panel owner & integrasi Midtrans.
// localStorage hanya berfungsi sebagai fallback/cache klien sementara.
const YK_ORDERS_KEY = 'yk_orders_v1';

function ykGetOrders() {
  // Kalau halaman ini nyuntik data pesanan asli dari database (lewat blade), pakai itu.
  // localStorage cuma fallback kalau belum login / belum ada koneksi ke server.
  if (Array.isArray(window.__YK_DB_ORDERS__)) {
    return [...window.__YK_DB_ORDERS__].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
  }
  let orders = [];
  try { orders = JSON.parse(localStorage.getItem(YK_ORDERS_KEY)) || []; }
  catch (e) { orders = []; }
  return orders.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
}

function ykSaveOrders(orders) {
  localStorage.setItem(YK_ORDERS_KEY, JSON.stringify(orders));
}

function ykGenerateOrderId() {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  const rand = Math.floor(1000 + Math.random() * 9000);
  return `YKB-${y}${m}${d}-${rand}`;
}

function ykAddOrder(order) {
  const orders = ykGetOrders();
  orders.push(order);
  ykSaveOrders(orders);
  return order;
}

function ykGetOrderById(id) {
  return ykGetOrders().find(o => o.id === id) || null;
}

function ykUpdateOrderStatus(id, status) {
  // Pesanan asli ada di database sekarang — beritahu server juga, bukan cuma localStorage.
  if (Array.isArray(window.__YK_DB_ORDERS__)) {
    const token = document.querySelector('meta[name="csrf-token"]');
    fetch(`/pesanan/${encodeURIComponent(id)}/cancel`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token ? token.content : '',
        'Accept': 'application/json',
      },
    }).catch(() => {});

    const dbOrder = window.__YK_DB_ORDERS__.find(o => o.id === id);
    if (dbOrder) dbOrder.status = status;
    return dbOrder || null;
  }

  const orders = ykGetOrders();
  const order = orders.find(o => o.id === id);
  if (!order) return null;
  order.status = status;
  ykSaveOrders(orders);
  return order;
}

// Format tanggal singkat, mengikuti bahasa aktif situs ('id' | 'en')
function ykFormatTanggal(dateStr, lang) {
  const bulanId = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  const bulanEn = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const dt = new Date(dateStr);
  if (isNaN(dt.getTime())) return '';
  const bulan = (lang === 'en') ? bulanEn : bulanId;
  return `${dt.getDate()} ${bulan[dt.getMonth()]} ${dt.getFullYear()}`;
}

function ykFormatTanggalWaktu(dateStr, lang) {
  const dt = new Date(dateStr);
  if (isNaN(dt.getTime())) return '';
  const jam = String(dt.getHours()).padStart(2, '0');
  const menit = String(dt.getMinutes()).padStart(2, '0');
  return `${ykFormatTanggal(dateStr, lang)}, ${jam}:${menit} WIB`;
}
