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
 
// ── Validar y sanear campos de entrada ───────────────────────────────────────
 
$eNumber   = trim((string) ($data['e_number']   ?? ''));
$eventCode = strtolower(trim((string) ($data['event_type'] ?? 'entrada')));
 
$source = in_array(strtolower((string) ($data['source'] ?? 'qr')), ['qr', 'manual', 'sync'], true)
    ? strtolower((string) $data['source'])
    : 'qr';
 
$clientUuid = trim((string) ($data['client_uuid'] ?? '')) ?: bin2hex(random_bytes(16));
 
if ($eNumber === '' || !in_array($eventCode, ['entrada', 'salida'], true)) {
    http_response_code(422);
    exit(json_encode(['ok' => false, 'message' => 'Datos incompletos o evento inválido.']));
}
 
if ($source === 'sync' && !empty($data['timestamp'])) {
    try {
        new DateTimeImmutable((string) $data['timestamp']);
    } catch (\Throwable $e) {
        http_response_code(422);
        exit(json_encode(['ok' => false, 'message' => 'Timestamp inválido.']));
    }
}
 
// consultas a la base de datos ─────────────────────────────────────────────
 
try {
    $db = Database::getConnection();
 
    $tzRow = $db->query(
        'SELECT NOW() AS server_now, TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), NOW()) AS offset_sec'
    )->fetch_assoc();
 
    $offsetSec = (int) ($tzRow['offset_sec'] ?? 0);
    $sign      = $offsetSec >= 0 ? '+' : '-';
    $absOffset = abs($offsetSec);
    $tzSpec    = sprintf('%s%02d:%02d', $sign, intdiv($absOffset, 3600), intdiv($absOffset % 3600, 60));
    $serverTz  = new DateTimeZone($tzSpec);
    $evalTime  = new DateTimeImmutable((string) ($tzRow['server_now'] ?? 'now'), $serverTz);
 
    if ($source === 'sync' && !empty($data['timestamp'])) {
        $evalTime = (new DateTimeImmutable((string) $data['timestamp']))->setTimezone($serverTz);
    }
 
    $checkedAt = $evalTime->format('Y-m-d H:i:s');
 
    $status = 'Asistió';
    if ($eventCode === 'entrada') {
        $status = ($evalTime->format('H:i:s') < '07:00:00') ? 'Asistió' : 'Retardo';
    }
 
    // buscar al usuario activo por número de empleado
    $stmt = $db->prepare('SELECT id, full_name FROM users WHERE e_number = ? AND is_active = 1 LIMIT 1');
    $stmt->bind_param('s', $eNumber);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
 
    if (!$user) {
        http_response_code(404);
        exit(json_encode(['ok' => false, 'message' => 'Usuario no encontrado o inactivo.']));
    }
 
    // obtener el id del evento (entrada/salida) desde la tabla de catálogo
    $stmt = $db->prepare('SELECT id, label FROM attendance_events WHERE code = ? LIMIT 1');
    $stmt->bind_param('s', $eventCode);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();
 
    if (!$event) {
        http_response_code(400);
        exit(json_encode(['ok' => false, 'message' => 'Evento inválido.']));
    }
 
    $userId  = (int) $user['id'];
    $eventId = (int) $event['id'];
 
    // insertar el log de asistencia de forma idempotente por client_uuid
    $hasStatusCol = Database::hasAttendanceStatusColumn();
 
    if ($hasStatusCol) {
        $stmt = $db->prepare(
            'INSERT IGNORE INTO attendance_logs (user_id, event_id, checked_at, client_uuid, source, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iissss', $userId, $eventId, $checkedAt, $clientUuid, $source, $status);
    } else {
        $stmt = $db->prepare(
            'INSERT IGNORE INTO attendance_logs (user_id, event_id, checked_at, client_uuid, source)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iisss', $userId, $eventId, $checkedAt, $clientUuid, $source);
    }
    $stmt->execute();
 
    $inserted = $stmt->affected_rows > 0;
    $logId    = $inserted ? (int) $db->insert_id : null;
    $stmt->close();
 
    echo json_encode([
        'ok'          => true,
        'duplicate'   => !$inserted,
        'log_id'      => $logId,
        'client_uuid' => $clientUuid,
        'user'        => ['e_number' => $eNumber, 'full_name' => $user['full_name']],
        'event'       => ['code' => $eventCode,   'label'     => $event['label']],
        'status'      => $status,
        'checked_at'  => $checkedAt,
        'check_time'  => $evalTime->format('H:i:s'),
        'message'     => $inserted ? 'Asistencia registrada.' : 'Ya sincronizado.'
    ]);
} catch (\Throwable $e) {
    error_log('Error en register.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error interno del servidor.']);
}
 
 