function getBaseUrl() {
  return document.body.dataset.baseUrl || '/ERSPC';
}

function setLoading(isLoading, text = 'Procesando...') {
  const overlay = document.getElementById('loadingOverlay');
  const label = document.getElementById('loadingText');
  if (!overlay) return;
  if (label) label.textContent = text;
  overlay.classList.toggle('d-none', !isLoading);
}

function showToast(title, message, type = 'success') {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const bg = {
    success: 'text-bg-success',
    danger: 'text-bg-danger',
    warning: 'text-bg-warning'
  }[type] || 'text-bg-secondary';

  const el = document.createElement('div');
  el.className = `toast ${bg} border-0 mb-2`;
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body"><strong>${escapeHtml(title)}:</strong> ${escapeHtml(message)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  `;
  container.appendChild(el);
  const toast = new bootstrap.Toast(el, { delay: 3000 });
  toast.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

function playBeepSound(ok = true) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    osc.frequency.value = ok ? 800 : 250;
    osc.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + 0.15);
  } catch (e) {}
}

function actualizarTablaRecientes(item) {
  const tbody = document.getElementById('recentChecksBody');
  const emptyRow = document.getElementById('emptyChecksRow');
  if (!tbody || !item) return;
  if (emptyRow) emptyRow.remove();

  const badge = item.is_offline
    ? '<span class="badge bg-warning text-dark">Offline</span>'
    : '<span class="badge bg-success">OK</span>';

  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>${escapeHtml(item.e_number || '')}</td>
    <td>${escapeHtml(item.event_type || '')}</td>
    <td>${escapeHtml(item.check_time || '')}</td>
    <td>${badge}</td>
  `;
  tbody.prepend(tr);
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function updateNetworkBadge() {
  const badge = document.getElementById('networkStatusBadge');
  if (!badge) return;
  badge.className = navigator.onLine ? 'badge bg-success ms-auto' : 'badge bg-warning text-dark ms-auto';
  badge.textContent = navigator.onLine ? 'En línea' : 'Sin conexión';
}

document.addEventListener('DOMContentLoaded', () => {
  updateNetworkBadge();
  window.addEventListener('online', updateNetworkBadge);
  window.addEventListener('offline', updateNetworkBadge);

  const btnMenu = document.getElementById('btnToggleMenu');
  const sidebar = document.getElementById('sidebarMenu');
  if (btnMenu && sidebar) {
    btnMenu.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
  }

  const clock = document.getElementById('liveClock');
  if (clock) {
    const updateClock = () => {
      clock.textContent = new Date().toLocaleTimeString('es-MX', { hour12: false });
    };
    updateClock();
    setInterval(updateClock, 1000);
  }
});