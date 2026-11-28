const OFFLINE_DB_NAME    = 'erpsc_offline_db';
const OFFLINE_DB_VERSION = 1;
const STORE_NAME         = 'pending_logs';
 
function getSyncApiEndpoint() {
  const base = typeof getBaseUrl === 'function' ? getBaseUrl() : '/ERPSC';
  return `${String(base).replace(/\/+$/, '')}/api/attendance/register.php`;
}
 
function abrirDB() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(OFFLINE_DB_NAME, OFFLINE_DB_VERSION);
 
    req.onupgradeneeded = (e) => {
      const db = e.target.result;
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        db.createObjectStore(STORE_NAME, { keyPath: 'client_uuid' });
      }
    };
 
    req.onsuccess = (e) => resolve(e.target.result);
    req.onerror   = (e) => reject(e.target.error);
  });
}
 
async function guardarOffline(payload) {
  try {
    const db = await abrirDB();
    return new Promise((resolve, reject) => {
      const tx  = db.transaction(STORE_NAME, 'readwrite');
      const req = tx.objectStore(STORE_NAME).put(payload);
      req.onsuccess = () => resolve();
      req.onerror   = (e) => reject(e.target.error);
      tx.oncomplete = () => db.close();
    });
  } catch (err) {
    console.error('[OfflineDB] Error al guardar:', err);
  }
}
 
async function obtenerPendientes() {
  try {
    const db = await abrirDB();
    return new Promise((resolve, reject) => {
      const tx  = db.transaction(STORE_NAME, 'readonly');
      const req = tx.objectStore(STORE_NAME).getAll();
      req.onsuccess = (e) => resolve(e.target.result || []);
      req.onerror   = (e) => reject(e.target.error);
      tx.oncomplete = () => db.close();
    });
  } catch (err) {
    console.error('[OfflineDB] Error al leer pendientes:', err);
    return [];
  }
}
 
async function eliminarPendiente(clientUuid) {
  try {
    const db = await abrirDB();
    return new Promise((resolve, reject) => {
      const tx  = db.transaction(STORE_NAME, 'readwrite');
      const req = tx.objectStore(STORE_NAME).delete(clientUuid);
      req.onsuccess = () => resolve();
      req.onerror   = (e) => reject(e.target.error);
      tx.oncomplete = () => db.close();
    });
  } catch (err) {
    console.error('[OfflineDB] Error al eliminar:', err);
  }
}
 
async function actualizarContadorCola() {
  const badge = document.getElementById('offlineQueueBadge');
  if (!badge) return;
 
  const pendientes = await obtenerPendientes();
  const n = pendientes.length;
 
  badge.textContent = `Cola: ${n}`;
  badge.className = n > 0 ? 'badge bg-warning text-dark' : 'badge bg-secondary';
}
 
async function sincronizarAhora() {
  if (!navigator.onLine) {
    if (typeof showToast === 'function') {
      showToast('Sin conexión', 'No hay red disponible para sincronizar.', 'warning');
    }
    return;
  }
 
  const pendientes = await obtenerPendientes();
  if (pendientes.length === 0) return;
 
  let sincronizados = 0;
  let fallidos      = 0;
  const endpoint    = getSyncApiEndpoint();
 
  for (const registro of pendientes) {
    try {
      const payload = { ...registro, source: 'sync' };
 
      const res = await fetch(endpoint, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(payload)
      });
 
      const json = await res.json();
 
      if (json.ok || json.duplicate) {
        await eliminarPendiente(registro.client_uuid);
        sincronizados++;
 
        if (typeof actualizarTablaRecientes === 'function') {
          const hora = registro.timestamp
            ? new Date(registro.timestamp).toLocaleTimeString('es-MX', { hour12: false })
            : new Date().toLocaleTimeString('es-MX', { hour12: false });
 
          actualizarTablaRecientes({
            id:          json.log_id || null,
            e_number:    json.user?.e_number || registro.e_number,
            event_type:  json.event?.code    || registro.event_type,
            event_label: json.event?.label   || registro.event_type,
            check_time:  json.check_time     || hora,
            status:      json.status         || 'Asistió',
            source:      'sync',
            client_uuid: registro.client_uuid,
            is_offline:  false
          });
        }
      } else {
        console.warn('[Sync] El servidor rechazó:', json.message, registro);
        fallidos++;
      }
    } catch (err) {
      console.error('[Sync] Error de red en registro:', err);
      fallidos++;
      break;
    }
  }
 
  await actualizarContadorCola();
 
  if (typeof showToast === 'function') {
    if (sincronizados > 0) {
      showToast('Sincronizado', `${sincronizados} registro(s) enviado(s).`, 'success');
    }
    if (fallidos > 0) {
      showToast('Sync parcial', `${fallidos} registro(s) no se pudieron enviar.`, 'warning');
    }
  }
}
 
document.addEventListener('DOMContentLoaded', () => {
  actualizarContadorCola();
 
  document.getElementById('btnSyncNow')?.addEventListener('click', sincronizarAhora);
 
  window.addEventListener('online', () => {
    if (typeof showToast === 'function') {
      showToast('Conexión restaurada', 'Sincronizando registros offline…', 'success');
    }
    sincronizarAhora();
  });
});
 