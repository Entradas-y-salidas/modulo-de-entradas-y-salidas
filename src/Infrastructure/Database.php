<?php
 
declare(strict_types=1);
 
namespace Erspc\Shared\Infrastructure;
 
use mysqli;
use RuntimeException;
 
final class Database
{
    // Instancia unica de la conexión 
    private static ?mysqli $connection = null;
 
    // Cache en memoria para saber si attendance_logs tiene la columna status.
    private static ?bool $hasAttendanceStatusColumn = null;
 
    /**
     * Devuelve la conexión mysqli, creándola si aún no existe.
     *
     * @throws RuntimeException Si no se puede conectar a la BD.
     */
    public static function getConnection(): mysqli
    {
        if (self::$connection instanceof mysqli) {
            return self::$connection;
        }
 
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
 
        $host     = getenv('ERSPC_DB_HOST') ?: 'localhost';
        $port     = (int) (getenv('ERSPC_DB_PORT') ?: 3306);$dbName   = getenv('ERSPC_DB_NAME') ?: 'erpsc_db';
        $username = getenv('ERSPC_DB_USER') ?: 'root';$password = getenv('ERSPC_DB_PASS') ?: '';
 
        try {
            $mysqli = new mysqli($host,$username, $password,$dbName, $port);$mysqli->set_charset('utf8mb4');
            self::$connection =$mysqli;
            return self::$connection;
        } catch (\Throwable $e) {
            error_log('Error de conexión MySQLi: ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar a la base de datos.');
        }
    }
 
    // Verifica si la tabla attendance_logs cuenta con la columna `status`.
    public static function hasAttendanceStatusColumn(): bool
    {
        if (self::$hasAttendanceStatusColumn !== null) {
            return self::$hasAttendanceStatusColumn;
        }
 
        $db = self::getConnection();
        $stmt =$db->prepare(
            'SELECT 1
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1'
        );
 
        $tableName  = 'attendance_logs';
        $columnName = 'status';$stmt->bind_param('ss', $tableName,$columnName);
        $stmt->execute();$result = $stmt->get_result();$exists = $result !== false &&$result->num_rows > 0;
        $stmt->close();
 
        self::$hasAttendanceStatusColumn =$exists;
        return $exists;
    }
}
 