<?php
$baseUrl = $baseUrl ?? '/ERPSC';
?>
<aside id="sidebarMenu" class="d-flex flex-column justify-content-between py-3">
    <div>
        <div class="px-3 pb-3 mb-2 border-bottom">
            <div class="small text-uppercase fw-bold text-uttn-primary" style="letter-spacing: 0.05em;">
            </div>
            <div class="text-muted" style="font-size: 0.78rem;">Control de Asistencia</div>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/">
                    <i class="bi bi-house-door"></i>
                    <span>Inicio</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/asistencias">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>Asistencias</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/reportes">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Reportes</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
