<?php
$baseUrl = $baseUrl ?? '/ERPSC';
?>
<footer class="bg-white border-top py-2 px-3 small text-muted text-center mt-auto">
Sistema Control de Asistencias
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/public/assets/js/ui.js"></script>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/public/assets/js/sync-worker.js"></script>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/public/assets/js/qr-scanner.js"></script>
</body>
</html>
