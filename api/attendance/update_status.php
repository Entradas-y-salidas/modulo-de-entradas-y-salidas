<?php
 
declare(strict_types=1);
 
require_once dirname(__DIR__, 2) . '/src/Shared/Infrastructure/Database.php';
 
use Erspc\Shared\Infrastructure\Database;
 
header('Content-Type: application/json; charset=utf-8');
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'message' => 'Usa POST.']));
}
 
$data = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($data)) {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'message' => 'JSON inválido.']));
}
 
$id     = isset($data['id']) ? (int) $data['id'] : 0;
$status = trim((string) ($data['status'] ?? ''));
 
$validStatuses = ['Asistió', 'Retardo', 'No asistió'];
 
if ($id <= 0 || !in_array($status, $validStatuses, true)) {
    http_response_code(422);
    exit(json_encode(['ok' => false, 'message' => 'ID o estado inválido.']));
}
 
try {
    $db = Database::getConnection();
 
    if (!Database::hasAttendanceStatusColumn()) {
        http_response_code(409);
        exit(json_encode([
            'ok'      => false,
            'message' => 'Falta la columna status en attendance_logs. Ejecuta el SQL ALTER TABLE en phpMyAdmin.'
        ]));
    }
 
    $stmt = $db->prepare('SELECT id FROM attendance_logs WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
 
    if (!$exists) {
        http_response_code(404);
        exit(json_encode(['ok' => false, 'message' => 'Registro no encontrado.']));
    }
 
    $stmt = $db->prepare('UPDATE attendance_logs SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    $stmt->close();
 
    echo json_encode([
        'ok'      => true,
        'id'      => $id,
        'status'  => $status,
        'message' => 'Estado actualizado correctamente.',
    ]);
} catch (\Throwable $e) {
    error_log('Error en update_status.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error interno del servidor.']);
}