<?php
?>
<div class="row g-4">

  <div class="col-lg-6">
    <div class="card card-uttn h-100">

      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold text-uttn-primary">
          <i class=""></i>Escáner QR
        </span>
        <div class="d-flex gap-2 align-items-center">
          <span id="networkStatusBadge" class="badge bg-secondary">En línea</span>
          <span id="offlineQueueBadge"  class="badge bg-secondary">Cola: 0</span>
        </div>
      </div>

      <div class="card-body">

        <!-- Selector de evento (Entrada / Salida) -->
        <div class="mb-3">
          <label for="selectEvent" class="form-label small fw-semibold text-muted mb-1">
            Tipo de evento
          </label>
          <select id="selectEvent" class="form-select form-select-sm">
            <option value="entrada" selected>Entrada</option>
            <option value="salida">Salida</option>
          </select>
        </div>

        <!-- Viewport del lector QR -->
        <div class="d-flex justify-content-center mb-3">
          <div id="qr-reader" class="qr-viewport-box text-white-50 small">
            Cámara detenida
          </div>
        </div>

        <!-- Botones de control de cámara y sincronización -->
        <div class="d-flex flex-wrap gap-2 mb-3">
          <button type="button" id="btnStartCamera" class="btn btn-uttn-primary btn-sm">
            <i class="bi bi-camera-video me-1"></i>Iniciar cámara
          </button>
          <button type="button" id="btnStopCamera" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-stop-circle me-1"></i>Detener
          </button>
          <button type="button" id="btnSyncNow" class="btn btn-uttn-outline btn-sm ms-auto">
            <i class="bi bi-arrow-repeat me-1"></i>Sincronizar
          </button>
        </div>

        <hr class="my-3">

        <!-- Formulario de marcaje manual por matrícula -->
        <label for="manualEmployeeNumber" class="form-label small fw-semibold text-muted mb-1">
          Registro manual por matrícula
        </label>
        <form id="manualCheckForm" class="d-flex gap-2">
          <input
            type="text"
            id="manualEmployeeNumber"
            class="form-control form-control-sm"
            placeholder="Matrícula (ej. 1001)"
            required
          >
          <button type="submit" id="btnManualSubmit" class="btn btn-dark btn-sm px-3">
            <i class="bi bi-check2-circle me-1"></i>Marcar
          </button>
        </form>

      </div>
    </div>
  </div>

  <!-- ── Columna derecha: tabla de marcajes recientes ── -->
  <div class="col-lg-6">
    <div class="card card-uttn h-100">

      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold text-uttn-primary">
          <i class="bi bi-clock-history me"></i>Marcajes recientes
        </span>
        <span class="clock-pill" id="liveClock">--:--:--</span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover table-uttn mb-0">
            <thead>
              <tr>
                <th class="ps-3">Matrícula</th>
                <th>Evento</th>
                <th>Hora</th>
                <th class="pe-3">Estado</th>
              </tr>
            </thead>
            <tbody id="recentChecksBody">
              <tr id="emptyChecksRow">
                <td colspan="4" class="text-center text-muted py-4">Sin registros aún</td>
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
