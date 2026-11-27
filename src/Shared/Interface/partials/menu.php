<?php
$baseUrl = $baseUrl ?? '/ERSPC';
?>
<aside id= "sidebar" class="bg-light border-end">
    <div class="sidebar-header p-3">
        <h5 class="mb-0">ERSPC</h5>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/">Inicio</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/asistencias">Asistencias</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/reportes">Reportes</a>
        </li>
    </ul>
</aside>