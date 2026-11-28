<?php
 
declare(strict_types=1);
 
require_once dirname(__DIR__, 2) . '/src/Shared/Infrastructure/Database.php';
 
use Erspc\Shared\Infrastructure\Database;
 
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    exit(json_encode(['ok' => false, 'message' => 'Usa GET.']));
}
 
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo   = trim((string) ($_GET['date_to'] ?? ''));
$status   = trim((string) ($_GET['status'] ?? ''));
$export   = strtolower(trim((string) ($_GET['export'] ?? '')));
 
$validStatuses = ['Asistió', 'Retardo', 'No asistió'];
 
if ($dateFrom !== '') {
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $dateFrom);
    if (!$d || $d->format('Y-m-d') !== $dateFrom) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(422);
        exit(json_encode(['ok' => false, 'message' => 'Fecha inicio inválida.']));
    }
}
 
if ($dateTo !== '') {
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $dateTo);
    if (!$d || $d->format('Y-m-d') !== $dateTo) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(422);
        exit(json_encode(['ok' => false, 'message' => 'Fecha fin inválida.']));
    }
}
 
if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(422);
    exit(json_encode(['ok' => false, 'message' => 'La fecha de inicio no puede ser mayor que la fecha fin.']));
}
 
if ($status !== '' && !in_array($status, $validStatuses, true)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(422);
    exit(json_encode(['ok' => false, 'message' => 'Estado inválido.']));
}
 
try {
    $db           = Database::getConnection();
    $hasStatusCol = Database::hasAttendanceStatusColumn();
    $statusExpr   = $hasStatusCol
        ? 'al.status'
        : "CASE WHEN ae.code = 'entrada' AND TIME(al.checked_at) >= '07:00:00' THEN 'Retardo' ELSE 'Asistió' END";
 
    $conditions = [];
    $types      = '';
    $params     = [];
 
    if ($dateFrom !== '') {
        $conditions[] = 'al.checked_at >= ?';
        $types       .= 's';
        $params[]     = $dateFrom . ' 00:00:00';
    }
 
    if ($dateTo !== '') {
        $conditions[] = 'al.checked_at <= ?';
        $types       .= 's';
        $params[]     = $dateTo . ' 23:59:59';
    }
 
    if ($status !== '') {
        $conditions[] = "($statusExpr) = ?";
        $types       .= 's';
        $params[]     = $status;
    }
 
    $whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';
 
    $sql = "SELECT
                al.id,
                u.e_number,
                u.full_name,
                ae.code AS event_type,
                ae.label AS event_label,
                al.checked_at,
                DATE_FORMAT(al.checked_at, '%Y-%m-%d') AS check_date,
                DATE_FORMAT(al.checked_at, '%H:%i:%s') AS check_time,
                al.source,
                ($statusExpr) AS status
            FROM attendance_logs al
            INNER JOIN users u ON u.id = al.user_id
            INNER JOIN attendance_events ae ON ae.id = al.event_id
            $whereSql
            ORDER BY al.checked_at DESC, al.id DESC";
 
    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
 
    $summary = [
        'Asistió'    => 0,
        'Retardo'    => 0,
        'No asistió' => 0,
        'total'      => count($records),
    ];
 
    foreach ($records as $row) {
        $rowStatus = $row['status'] ?? 'Asistió';
        if (isset($summary[$rowStatus])) {
            $summary[$rowStatus]++;
        }
    }
 
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'           => true,
        'schema_ready' => $hasStatusCol,
        'summary'      => $summary,
        'records'      => $records,
    ]);
} catch (\Throwable $e) {
    error_log('Error en report.php: ' . $e->getMessage());
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'message' => 'Error al generar el reporte de asistencia.',
    ]);
}
 
 