function getBaseUrl() {
  return document.body.dataset.baseUrl || '/ERPSC';
}
 
/* ─── cargando overlay ────────────────────────────────────────────────────── */
 
function setLoading(isLoading) {
  const overlay = document.getElementById('loading');
  if (!overlay) return;
  overlay.classList.toggle('d-none', !isLoading);
}
 
/* ─── Toasts de Bootstrap ────────────────────────────────────────────────── */
 
function showToast(title, message, type = 'success') {
  const container = document.getElementById('toastContainer');
  if (!container) return;
 
  const bgClass = {
    success: 'text-bg-success',
    danger:  'text-bg-danger',
    warning: 'text-bg-warning'
  }[type] || 'text-bg-secondary';
 
  const el = document.createElement('div');
  el.className = `toast ${bgClass} border-0 mb-2`;
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body"><strong>${escapeHtml(title)}:</strong> ${escapeHtml(message)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  `;
  container.appendChild(el);
  const toast = new bootstrap.Toast(el, { delay: 3500 });
  toast.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}
 
/* ─── Beep de confirmación ───────────────────────────────────────────────── */
 
function playBeepSound(ok = true) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    osc.frequency.value = ok ? 800 : 250;
    osc.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + 0.15);
  } catch (e) { /* El navegador puede bloquear AudioContext sin interacción previa */ }
}
 
/* ─── Actualización de estado en servidor ────────────────────────────────── */
 
if (typeof window.actualizarEstadoRegistro !== 'function') {
  window.actualizarEstadoRegistro = async function (id, status) {
    try {
      const res = await fetch(`${getBaseUrl()}/api/attendance/update_status.php`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ id: Number(id), status })
      });
      const json = await res.json();
      if (!res.ok || !json.ok) {
        showToast('Error', json?.message || 'No se pudo actualizar el estado.', 'danger');
        return false;
      }
      showToast('Estado actualizado', `Nuevo estado: ${status}`, 'success');
      return true;
    } catch (err) {
      console.error('[Estado] Error al actualizar:', err);
      showToast('Error', 'Error de conexión al actualizar el estado.', 'danger');
      return false;
    }
  };
}
 
/* tabla de marcajes recientes */
 
const ESTADOS_ASISTENCIA = ['Asistió', 'Retardo', 'No asistió'];
 
const BADGE_ESTADO_MAP = {
  'Asistió':    'bg-success',
  'Retardo':    'bg-warning text-dark',
  'No asistió': 'bg-danger'
};
 
function _crearBadgeEstado(item) {
  if (item.is_offline) {
    return '<span class="badge bg-warning text-dark">Offline</span>';
  }
  if (item.source === 'sync' && !item.status) {
    return '<span class="badge bg-info text-dark">Sync</span>';
  }
 
  const estado = item.status || 'Asistió';
  const badgeClass = BADGE_ESTADO_MAP[estado] || 'bg-secondary';
  const badgeHtml = `<span class="badge ${badgeClass} js-recent-status-badge">${escapeHtml(estado)}</span>`;
 
  if (!item.id) {
    return badgeHtml;
  }
 
  const opciones = ESTADOS_ASISTENCIA.map((opt) => {
    const selected = opt === estado ? 'selected' : '';
    return `<option value="${opt}" ${selected}>${opt}</option>`;
  }).join('');
 
  return `
    <div class="d-flex align-items-center gap-1">
      ${badgeHtml}
      <select
        class="form-select form-select-sm py-0 px-1 js-recent-status-select"
        style="width: auto; font-size: 0.75rem;"
        data-id="${Number(item.id)}"
        data-prev="${escapeHtml(estado)}"
        aria-label="Cambiar estado de asistencia"
      >${opciones}</select>
    </div>
  `;
}
 
function actualizarTablaRecientes(item) {
  const tbody    = document.getElementById('recentChecksBody');
  const emptyRow = document.getElementById('emptyChecksRow');
  if (!tbody || !item) return;
 
  if (emptyRow) emptyRow.remove();
 
  const eventoTexto = item.event_label || item.event_type || '';
  const badgeHtml   = _crearBadgeEstado(item);
  const celdasHtml  = `
    <td>${escapeHtml(item.e_number || '')}</td>
    <td>${escapeHtml(eventoTexto)}</td>
    <td>${escapeHtml(item.check_time || '')}</td>
    <td>${badgeHtml}</td>
  `;
 
  if (item.client_uuid) {
    const filaExistente = tbody.querySelector(`tr[data-uuid="${CSS.escape(item.client_uuid)}"]`);
    if (filaExistente) {
      filaExistente.innerHTML = celdasHtml;
      return;
    }
  }
 
  const tr = document.createElement('tr');
  if (item.client_uuid) {
    tr.dataset.uuid = item.client_uuid;
  }
  tr.innerHTML = celdasHtml;
  tbody.prepend(tr);
 
  while (tbody.rows.length > 15) {
    tbody.lastElementChild.remove();
  }
}
 
async function cargarMarcajesRecientes() {
  const tbody = document.getElementById('recentChecksBody');
  if (!tbody || !navigator.onLine) return;
 
  try {
    const res  = await fetch(`${getBaseUrl()}/api/attendance/recent.php?limit=10`);
    const json = await res.json();
 
    if (!json.ok || !Array.isArray(json.records) || json.records.length === 0) {
      return;
    }
 
    for (let i = json.records.length - 1; i >= 0; i--) {
      const reg = json.records[i];
      actualizarTablaRecientes({
        id:          reg.id,
        e_number:    reg.e_number,
        event_type:  reg.event_type,
        event_label: reg.event_label,
        check_time:  reg.check_time,
        status:      reg.status,
        source:      reg.source,
        client_uuid: reg.client_uuid,
        is_offline:  false
      });
    }
  } catch (err) {
    console.warn('[Recientes] No se pudieron cargar los registros previos:', err);
  }
}
 
/* Escape HTML */
 
function escapeHtml(str) {
  return String(str)
    .replace(/&/g,  '&amp;')
    .replace(/</g,  '&lt;')
    .replace(/>/g,  '&gt;')
    .replace(/"/g,  '&quot;')
    .replace(/'/g,  '&#039;');
}
 
/* Badge de estado de red */
 
function updateNetworkBadge() {
  const badge = document.getElementById('networkStatusBadge');
  if (!badge) return;
  if (navigator.onLine) {
    badge.className   = 'badge bg-success ms-auto';
    badge.textContent = 'En línea';
  } else {
    badge.className   = 'badge bg-warning text-dark ms-auto';
    badge.textContent = 'Sin conexión';
  }
}
 
/* Inicialización */
 
document.addEventListener('DOMContentLoaded', () => {
  updateNetworkBadge();
  window.addEventListener('online',  updateNetworkBadge);
  window.addEventListener('offline', updateNetworkBadge);
 
  cargarMarcajesRecientes();
 
  document.getElementById('recentChecksBody')?.addEventListener('change', async (e) => {
    const select = e.target.closest('.js-recent-status-select');
    if (!select) return;
 
    const id = Number(select.dataset.id);
    const nuevoEstado = select.value;
    const prevEstado = select.dataset.prev || 'Asistió';
 
    select.disabled = true;
    const ok = await window.actualizarEstadoRegistro(id, nuevoEstado);
    select.disabled = false;
 
    if (ok) {
      select.dataset.prev = nuevoEstado;
      const badge = select.closest('td')?.querySelector('.js-recent-status-badge');
      if (badge) {
        badge.className = `badge ${BADGE_ESTADO_MAP[nuevoEstado] || 'bg-secondary'} js-recent-status-badge`;
        badge.textContent = nuevoEstado;
      }
    } else {
      select.value = prevEstado;
    }
  });
 
  const btnMenu = document.getElementById('btnToggleMenu');
  const sidebar = document.getElementById('sidebarMenu');
  if (btnMenu && sidebar) {
    btnMenu.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
  }
 
  const clock = document.getElementById('liveClock');
  if (clock) {
    const tick = () => {
      clock.textContent = new Date().toLocaleTimeString('es-MX', { hour12: false });
    };
    tick();
    setInterval(tick, 1000);
  }
});