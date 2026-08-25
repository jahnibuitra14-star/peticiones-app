<?php
// Configurar zona horaria oficial de Venezuela
date_default_timezone_set('America/Caracas');

$todos_los_registros = [];
$registros_por_fecha = [];

try {
    // Variables de Entorno de Railway con fallbacks compatibles
    $host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: '127.0.0.1';
    $db   = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'railway';
    $user = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';
    $port = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: '3306';

    // Instancia PDO dentro del bloque try
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // LÓGICA PARA ELIMINAR UNA PETICIÓN INDIVIDUAL
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'eliminar_uno') {
        $id_eliminar = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id_eliminar) {
            $stmt_delete = $pdo->prepare("DELETE FROM registros WHERE id = :id");
            $stmt_delete->execute([':id' => $id_eliminar]);
            header("Location: admin.php?status=deleted");
            exit;
        }
    }

    // LÓGICA PARA ELIMINAR TODO EL HISTORIAL COMPLETO
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'eliminar_todo') {
        $stmt_delete = $pdo->prepare("DELETE FROM registros");
        $stmt_delete->execute();
        header("Location: admin.php?status=all_deleted");
        exit;
    }
    
    // CONSULTA USANDO LA COLUMNA CORRECTA: fecha_registro
    $stmt = $pdo->prepare("SELECT *, DATE(fecha_registro) as fecha_dia FROM registros ORDER BY fecha_registro DESC");
    $stmt->execute();
    $todos_los_registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // AGRUPAR REGISTROS POR FECHA
    foreach ($todos_los_registros as $row) {
        $fecha = $row['fecha_dia'];
        $registros_por_fecha[$fecha][] = $row;
    }

} catch (PDOException $e) {
    die("<body style='background-color:#FDF6EC;font-family:sans-serif;padding:20px;'>
        <div style='background:white;padding:20px;border-radius:8px;color:#C62828;border:1px solid #FFCDD2;'>
            <strong>Error de conexión a la base de datos:</strong> " . htmlspecialchars($e->getMessage()) . "
        </div>
    </body>");
}
?>
