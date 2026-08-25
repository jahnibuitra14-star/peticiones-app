<?php
// Configurar zona horaria oficial de Venezuela en PHP
date_default_timezone_set('America/Caracas');

// Configuración para captura de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);

$mensaje_exito = "";
$mensaje_error = "";

// PROCESAR EL FORMULARIO CUANDO SE ENVÍA (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $peticion = trim($_POST['peticion'] ?? '');

    if (!empty($nombre) && !empty($peticion)) {
        try {
            // Lectura preferente de URL directa de base de datos
            $db_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: getenv('MYSQLURL');

            if ($db_url) {
                $dbopts = parse_url($db_url);
                $host = $dbopts['host'] ?? '127.0.0.1';
                $port = $dbopts['port'] ?? '3306';
                $user = $dbopts['user'] ?? 'root';
                $pass = $dbopts['pass'] ?? '';
                $db   = isset($dbopts['path']) ? ltrim($dbopts['path'], '/') : 'railway';
            } else {
                $host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: '127.0.0.1';
                $db   = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'railway';
                $user = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: 'root';
                $pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: getenv('MYSQL_ROOT_PASSWORD') ?: '';
                $port = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: '3306';
            }

            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // FORZAR A MYSQL A USAR LA ZONA HORARIA DE VENEZUELA (UTC-4)
            $pdo->exec("SET time_zone = '-04:00';");

            // Inserción en la base de datos usando sentencias preparadas
            $stmt = $pdo->prepare("INSERT INTO registros (nombre, peticion, fecha_registro) VALUES (:nombre, :peticion, NOW())");
            $stmt->execute([
                ':nombre'   => $nombre,
                ':peticion' => $peticion
            ]);

            $mensaje_exito = "¡Tu petición ha sido enviada con éxito!";
        } catch (PDOException $e) {
            $mensaje_error = "Error al procesar la petición. Inténtalo de nuevo en unos momentos.";
        }
    } else {
        $mensaje_error = "Por favor, completa todos los campos requeridos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Petición de Oración</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background-color: #FDF6EC;
            color: #5D4037;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .form-card {
            background-color: #FFFFFF;
            padding: 30px 25px;
            border-radius: 12px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 4px 15px rgba(93, 64, 55, 0.08);
            border: 1px solid #F3E5DC;
        }
        h2 {
            margin-top: 0;
            color: #5D4037;
            font-size: 22px;
            text-align: center;
            margin-bottom: 20px;
        }
        .alerta-error {
            background-color: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.4;
            word-break: break-word;
        }
        .alerta-exito {
            background-color: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            font-weight: 600;
        }
        .form-group { margin-bottom: 18px; }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            color: #5D4037;
            font-size: 14px;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #E6D7CF;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            color: #333;
            outline: none;
            transition: border-color 0.2s;
        }
        input[type="text"]:focus, textarea:focus { border-color: #F48FB1; }
        textarea { resize: vertical; min-height: 110px; }
        button[type="submit"] {
            width: 100%;
            background-color: #F48FB1;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: background-color 0.2s;
        }
        button[type="submit"]:hover { background-color: #F06292; }
    </style>
</head>
<body>

<div class="form-card">
    <h2>Enviar Petición</h2>

    <?php if (!empty($mensaje_error)): ?>
        <div class="alerta-error"><?php echo $mensaje_error; ?></div>
    <?php endif; ?>

    <?php if (!empty($mensaje_exito)): ?>
        <div class="alerta-exito"><?php echo $mensaje_exito; ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php">
        <div class="form-group">
            <label for="nombre">Nombre completo:</label>
            <input type="text" id="nombre" name="nombre" placeholder="Tu nombre y apellido" required autocomplete="off">
        </div>

        <div class="form-group">
            <label for="peticion">Escribe tu petición:</label>
            <textarea id="peticion" name="peticion" placeholder="Escribe aquí el motivo de oración..." required></textarea>
        </div>

        <button type="submit">Enviar Petición</button>
    </form>
</div>

</body>
</html>
