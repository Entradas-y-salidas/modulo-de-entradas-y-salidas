<?php

declare(strict_types=1);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$baseUrl = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');

require_once __DIR__ . '/src/Shared/Interface/layout.php';

renderLayout(
    'Control de Asistencias',
    static function () use ($baseUrl): void {
        require __DIR__ . '/src/Modules/Attendance/Views/scanner.php';
    },
    $baseUrl
);