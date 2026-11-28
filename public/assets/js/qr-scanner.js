let mediaStream = null;
let isScanning  = false;
 
function resolveApiUrl() {
  const base = typeof getBaseUrl === 'function' ? getBaseUrl() : '/ERPSC';
  return `${String(base).replace(/\/+$/, '')}/api/attendance/register.php`;
}
 
 
async function startCamera() {
  if (isScanning) return;
  const readerEl = document.getElementById('qr-reader');
  if (!readerEl) return;
 
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    readerEl.textContent = 'Tu navegador no soporta acceso a la cámara.';
    return;
  }
 
  try {
    try {
      mediaStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' },
        audio: false
      });
    } catch (_) {
      mediaStream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: false
      });
    }
 
    const video = document.createElement('video');
    video.autoplay = true;
    video.playsInline = true;
    video.muted = true;
    video.style.width = '100%';
    video.style.height = '100%';
    video.style.objectFit = 'cover';
    video.srcObject = mediaStream;
 
    readerEl.innerHTML = '';
    readerEl.appendChild(video);
    isScanning = true;
  } catch (err) {
    readerEl.textContent = 'Error al abrir la cámara: ' + err.message;
    console.error(err);
  }
}
 
async function stopCamera() {
  if (!isScanning && !mediaStream) return;
  try {
    if (mediaStream) {
      mediaStream.getTracks().forEach((track) => track.stop());
      mediaStream = null;
    }
    isScanning = false;
    const readerEl = document.getElementById('qr-reader');
    if (readerEl) readerEl.textContent = 'Cámara detenida';
  } catch (err) {
    console.error(err);
  }
}
 
function generarUUID() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
  });
}
 
async function registrarMarcaje(eNumber, eventType, source) {
  const clientUuid = generarUUID();
  const ahora      = new Date();
  const timestamp  = ahora.toISOString();
  const horaLocal  = ahora.toLocaleTimeString('es-MX', { hour12: false });
 
  const payload = {
    e_number:    eNumber,
    event_type:  eventType,
    source:      source,
    client_uuid: clientUuid,
    timestamp:   timestamp
  };
 
  if (!navigator.onLine) {
    await _guardarOfflineYNotificar(payload, horaLocal);
    return;
  }
 
  try {
    const res = await fetch(resolveApiUrl(), {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload)
    });
 
    const json = await res.json();
 
    if (json.ok) {
      if (typeof actualizarTablaRecientes === 'function') {
        actualizarTablaRecientes({
          id:          json.log_id || null,
          e_number:    json.user?.e_number || eNumber,
          event_type:  json.event?.code    || eventType,
          event_label: json.event?.label   || eventType,
          check_time:  json.check_time     || horaLocal,
          status:      json.status         || 'Asistió',
          source:      source,
          client_uuid: clientUuid,
          is_offline:  false
        });
      }
      if (typeof playBeepSound === 'function') playBeepSound(true);
      if (typeof showToast === 'function') {
        const nombre = json.user?.full_name || eNumber;
        const estadoTexto = json.status ? ` (${json.status})` : '';
        showToast('Marcaje OK', `${nombre} → ${json.event?.label || eventType}${estadoTexto}`, 'success');
      }
    } else {
      if (typeof playBeepSound === 'function') playBeepSound(false);
      if (typeof showToast === 'function') {
        showToast('Error', json.message || 'No se pudo registrar.', 'danger');
      }
    }
  } catch (err) {
    console.warn('[Registro] fetch falló, guardando offline:', err);
    await _guardarOfflineYNotificar(payload, horaLocal);
  }
}
 
 
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('btnStartCamera')?.addEventListener('click', startCamera);
  document.getElementById('btnStopCamera')?.addEventListener('click', stopCamera);
 
  document.getElementById('manualCheckForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const input = document.getElementById('manualEmployeeNumber');
    const val   = input?.value.trim();
    if (!val) return;
    registrarMarcaje(val, document.getElementById('selectEvent')?.value || 'entrada', 'manual');
    input.value = '';
  });
});
 