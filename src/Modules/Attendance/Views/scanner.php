<?php
?>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card border">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>Escáner QR</strong>
          <span id="offlineQueueBadge" class="badge bg-secondary">Cola: 0</span>
        </div>

        <div class="mb-3">
          <label for="selectEvent" class="form-label">Evento</label>
          <select id="selectEvent" class="form-select form-select-sm">
            <option value="entrada" selected>Entrada</option>
            <option value="salida">Salida</option>
          </select>
        </div>

        <div class="d-flex justify-content-center mb-3">
          <div id="qr-reader" class="qr-viewport-box text-white small">
            Cámara detenida
          </div>
        </div>

        <div class="d-flex gap-2 mb-3">
          <button type="button" id="btnStartCamera" class="btn btn-uttn-primary btn-sm">Iniciar cámara</button>
          <button type="button" id="btnStopCamera" class="btn btn-outline-secondary btn-sm">Detener</button>
          <button type="button" id="btnSyncNow" class="btn btn-outline-success btn-sm">Sincronizar</button>
        </div>

        <form id="manualCheckForm" class="d-flex gap-2">
          <input
            type="text"
            id="manualEmployeeNumber"
            class="form-control form-control-sm"
            placeholder="Matrícula (ej. DOC-2026-01)"
            required
          >
          <button type="submit" id="btnManualSubmit" class="btn btn-dark btn-sm">Marcar</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card border">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>Marcajes recientes</strong>
          <span class="small text-muted" id="liveClock">--:--:--</span>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr>
                <th>Matrícula</th>
                <th>Evento</th>
                <th>Hora</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody id="recentChecksBody">
              <tr id="emptyChecksRow">
                <td colspan="4" class="text-center text-muted py-3">Sin registros aún</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>