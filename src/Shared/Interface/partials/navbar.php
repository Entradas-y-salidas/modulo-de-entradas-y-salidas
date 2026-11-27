<?php
$baseUrl = $baseUrl ?? '/ERPSC';
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-uttn sticky-top">
    <div class="container-fluid px-3">
        <button id="btnToggleMenu" class="btn btn-sm btn-outline-light me-2 border-0" type="button" title="Mostrar u ocultar menú">
            <i class="bi bi-list fs-5"></i>
        </button>

        <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/">
            <span class="d-flex flex-column lh-1">
                <span class="fs-6">Modulo de Control de Asistencias</span>
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link active" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/">
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/reportes">
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
