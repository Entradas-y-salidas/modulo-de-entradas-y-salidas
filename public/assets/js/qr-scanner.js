let html5QrCode = null;
let isScanning = false;
let isProcessing = false;

async function startCamera() {
  if (isScanning) return;
  const readerEl = document.getElementById('qr-reader');
  if (!readerEl) return;

  if (typeof Html5Qrcode === 'undefined') {
    alert('No se cargó la librería html5-qrcode. Revisa tu conexión a internet.');
    return;
  }

  try {
    html5QrCode = new Html5Qrcode('qr-reader');
    const cameras = await Html5Qrcode.getCameras();

    if (!cameras || cameras.length === 0) {
      readerEl.textContent = 'No se detectó ninguna cámara conectada.';
      return;
    }

    await html5QrCode.start(
      cameras[0].id,
      { fps: 10, qrbox: { width: 220, height: 220 } },
      onScanSuccess,
      () => {} // Ignorar errores de frame sin QR
    );

    isScanning = true;
  } catch (err) {
    readerEl.textContent = 'Error al abrir la cámara: ' + err;
    console.error(err);
  }
}

async function stopCamera() {
  if (!html5QrCode || !isScanning) return;
  try {
    await html5QrCode.stop();
    await html5QrCode.clear();
    isScanning = false;
    const readerEl = document.getElementById('qr-reader');
    if (readerEl) readerEl.textContent = 'Cámara detenida';
  } catch (err) {
    console.error(err);
  }
}

function onScanSuccess(qrCodeMessage) {
  if (isProcessing) return;
  isProcessing = true;

  const matricula = qrCodeMessage.trim();
  const eventType = document.getElementById('selectEvent')?.value || 'entrada';

  // Por ahora (sin backend aún), lo mostramos en la tabla y lanzamos alerta
  if (typeof playBeepSound === 'function') playBeepSound(true);
  if (typeof showToast === 'function') {
    showToast('QR Leído', `${matricula} (${eventType})`, 'success');
  }
  if (typeof actualizarTablaRecientes === 'function') {
    actualizarTablaRecientes({
      e_number: matricula,
      event_type: eventType,
      check_time: new Date().toLocaleTimeString('es-MX', { hour12: false }),
      is_offline: false
    });
  }

  // Evitar lecturas repetidas seguidas por 2 segundos en frontend
  setTimeout(() => {
    isProcessing = false;
  }, 2000);
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('btnStartCamera')?.addEventListener('click', startCamera);
  document.getElementById('btnStopCamera')?.addEventListener('click', stopCamera);

  // Respaldo manual por matrícula
  document.getElementById('manualCheckForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const input = document.getElementById('manualEmployeeNumber');
    const val = input?.value.trim();
    if (!val) return;
    onScanSuccess(val);
    input.value = '';
  });
});