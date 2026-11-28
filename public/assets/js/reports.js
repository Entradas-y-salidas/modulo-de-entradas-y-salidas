function _obtenerParamsReporte() {
  const params = new URLSearchParams();
  const dateFrom = document.getElementById('reportDateFrom')?.value || '';
  const dateTo   = document.getElementById('reportDateTo')?.value || '';
  const status   = document.getElementById('reportStatus')?.value || '';
 
  if (dateFrom) params.set('date_from', dateFrom);
  if (dateTo)   params.set('date_to', dateTo);
  if (status)   params.set('status', status);
 
  return params;
}
 
async function _obtenerDatosReporte() {
  const params = _obtenerParamsReporte();
  const query  = params.toString();
  const url    = `${getBaseUrl()}/api/attendance/report.php${query ? `?${query}` : ''}`;
 
  const res  = await fetch(url);
  const json = await res.json();
 
  if (!res.ok || !json.ok) {
    throw new Error(json?.message || 'No se pudo cargar el reporte.');
  }
 
  return {
    params,
    summary: json.summary || { total: 0, 'Asistió': 0, 'Retardo': 0, 'No asistió': 0 },
    records: Array.isArray(json.records) ? json.records : []
  };
}
 
function _construirElementoPdf({ params, summary, records }) {
  const df = params.get('date_from');
  const dt = params.get('date_to');
  const st = params.get('status');
 
  const filtrosTexto = [
    (df && dt) ? `Del ${df} al ${dt}` : (df ? `Desde ${df}` : (dt ? `Hasta ${dt}` : 'Todas las fechas')),
    st ? `Estado: ${st}` : 'Estado: Todos'
  ].join(' · ');
 
  const filasHtml = records.length > 0
    ? records.map((reg) => `
        <tr>
          <td>${escapeHtml(reg.e_number || '')}</td>
          <td>${escapeHtml(reg.full_name || '')}</td>
          <td>${escapeHtml(reg.event_label || reg.event_type || '')}</td>
          <td>${escapeHtml(reg.check_date || '')}</td>
          <td>${escapeHtml(reg.check_time || '')}</td>
          <td>${escapeHtml(reg.source || '')}</td>
          <td>${escapeHtml(reg.status || 'Asistió')}</td>
        </tr>
      `).join('')
    : '<tr><td colspan="7" style="text-align:center;">Sin registros para el filtro seleccionado.</td></tr>';
 
  const contenedor = document.createElement('div');
  contenedor.innerHTML = `
    <style>
      .pdf-wrap { font-family: Arial, sans-serif; color: #212529; padding: 6px; }
      .pdf-wrap h4 { margin: 0 0 4px; }
      .pdf-meta { margin: 0 0 10px; font-size: 12px; color: #555; }
      .pdf-summary { margin: 0 0 12px; font-size: 12px; }
      .pdf-table { width: 100%; border-collapse: collapse; font-size: 11px; }
      .pdf-table th, .pdf-table td { padding: 5px 7px; border: 1px solid #dee2e6; text-align: left; }
      .pdf-table thead th { background: #f1f3f5; }
    </style>
    <div class="pdf-wrap">
      <h4>Reporte de Asistencia</h4>
      <p class="pdf-meta">${escapeHtml(filtrosTexto)}</p>
      <p class="pdf-summary">
        <strong>Total:</strong> ${Number(summary.total ?? 0)} |
        <strong>Asistió:</strong> ${Number(summary['Asistió'] ?? 0)} |
        <strong>Retardo:</strong> ${Number(summary['Retardo'] ?? 0)} |
        <strong>No asistió:</strong> ${Number(summary['No asistió'] ?? 0)}
      </p>
      <table class="pdf-table">
        <thead>
          <tr>
            <th>Matrícula</th>
            <th>Nombre</th>
            <th>Evento</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Origen</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>${filasHtml}</tbody>
      </table>
    </div>
  `;
 
  return contenedor;
}
 
async function descargarReportePDF() {
  if (typeof html2pdf === 'undefined') {
    showToast?.('Error', 'No se cargó la librería para generar PDF.', 'danger');
    return;
  }
 
  try {
    setLoading?.(true);
 
    const data = await _obtenerDatosReporte();
    const df   = data.params.get('date_from');
    const dt   = data.params.get('date_to');
    const fecha = (df && dt) ? `${df}_a_${dt}` : (df || dt || new Date().toISOString().slice(0, 10));
    const filename = `reporte_asistencia_${fecha}.pdf`;
 
    await html2pdf().set({
      margin:      10,
      filename,
      image:       { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true },
      jsPDF:       { unit: 'mm', format: 'letter', orientation: 'landscape' }
    }).from(_construirElementoPdf(data)).save();
 
    showToast?.('PDF generado', `Se descargó ${filename}`, 'success');
  } catch (err) {
    console.error('[Reportes] Error al generar PDF:', err);
    showToast?.('Error', err?.message || 'No se pudo descargar el archivo PDF.', 'danger');
  } finally {
    setLoading?.(false);
  }
}
 
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('reportFilterForm');
  if (!form) return;
 
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    descargarReportePDF();
  });
 
  document.getElementById('btnResetFilters')?.addEventListener('click', () => {
    form.reset();
  });
 
  document.getElementById('btnDownloadPdf')?.addEventListener('click', descargarReportePDF);
});
 