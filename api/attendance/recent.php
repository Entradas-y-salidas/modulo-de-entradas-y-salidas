<?php
 
declare(strict_types=1);
 
require_once dirname(__DIR__, 2) . '/../../Infrastructure/Database.php';
 
use Erspc\Shared\Infrastructure\Database;
 
header('Content-Type: application/json; charset=utf-8');
 
// Solo permite GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'message' => 'Usa GET.']));
}
 
// Limitar cantidad de registros a devolver (por defecto 10, máximo 50)
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$limit = max(1, min(50, $limit));
 
try {
    $db           = Database::getConnection();
    $hasStatusCol = Database::hasAttendanceStatusColumn();
    $statusExpr   = $hasStatusCol
        ? 'al.status'
        : "CASE WHEN ae.code = 'entrada' AND TIME(al.checked_at) >= '07:00:00' THEN 'Retardo' ELSE 'Asistió' END";
 
    // Unir attendance_logs con users y attendance_events para traer matrícula, evento y estado
    $sql = "SELECT
                al.id,
                u.e_number,
                u.full_name,
                ae.code AS event_type,
                ae.label AS event_label,
                al.checked_at,
                DATE_FORMAT(al.checked_at, '%H:%i:%s') AS check_time,
                al.client_uuid,
                al.source,
                ($statusExpr) AS status
            FROM attendance_logs al
            INNER JOIN users u ON u.id = al.user_id
            INNER JOIN attendance_events ae ON ae.id = al.event_id
            ORDER BY al.checked_at DESC, al.id DESC
            LIMIT ?";
 
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result  = $stmt->get_result();
    $records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
 
    echo json_encode([
        'ok'           => true,
        'schema_ready' => $hasStatusCol,
        'records'      => $records,
    ]);
} catch (\Throwable $e) {
    error_log('Error en recent.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'message' => 'Error al consultar los marcajes recientes.',
    ]);
}