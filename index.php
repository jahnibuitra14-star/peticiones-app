<?php
// Ocultar errores técnicos al usuario en producción
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar zona horaria de Venezuela en PHP
date_default_timezone_set('America/Caracas');

// CONFIGURACIÓN Y CONEXIÓN A LA BASE DE DATOS
$host = $_ENV['MYSQLHOST'] ?? '127.0.0.1';
$port = $_ENV['MYSQLPORT'] ?? '3306';
$db   = $_ENV['MYSQLDATABASE'] ?? 'railway';
$user = $_ENV['MYSQLUSER'] ?? 'root';
$pass = $_ENV['MYSQLPASSWORD'] ?? '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$mensaje_exito = "";
$mensaje_error = "";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Forzar la zona horaria de Venezuela en la sesión de MySQL
    $pdo->exec("SET time_zone = '-04:00'");
    
    // Forzar conjunto de caracteres utf8mb4 para emojis y tildes
    $pdo->exec("SET NAMES utf8mb4");

} catch (PDOException $e) {
    $mensaje_error = "Error de conexión: " . $e->getMessage();
}

// PROCESAR ENVÍO DEL FORMULARIO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enviar_peticion'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $peticion = trim($_POST['peticion'] ?? '');

    if (!empty($nombre) && !empty($peticion)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO registros (nombre, peticion, fecha_registro) VALUES (:nombre, :peticion, NOW())");
            $stmt->execute([
                ':nombre' => $nombre,
                ':peticion' => $peticion
            ]);
            $mensaje_exito = "¡Tu petición ha sido enviada con éxito!";
        } catch (PDOException $e) {
            // Mensaje genérico de producción para el usuario
            $mensaje_error = "Error al procesar la petición. Inténtalo de nuevo en unos momentos.";
        }
    } else {
        $mensaje_error = "Por favor, completa todos los campos requeridos.";
    }
}

// LÓGICA DEL VERSÍCULO DEL DÍA (31 VERSÍCULOS EN ROTACIÓN DIARIA)
$versiculos = [
    ["texto" => "La oración eficaz del justo puede mucho.", "cita" => "Santiago 5:16"],
    ["texto" => "Clama a mí, y yo te responderé, y te enseñaré cosas grandes y ocultas que tú no conoces.", "cita" => "Jeremías 33:3"],
    ["texto" => "Por nada estéis afanosos, sino sean conocidas vuestras peticiones delante de Dios en toda oración y ruego, con acción de gracias.", "cita" => "Filipenses 4:6"],
    ["texto" => "Esta es la confianza que tenemos en él, que si pedimos alguna cosa conforme a su voluntad, él nos oye.", "cita" => "1 Juan 5:14"],
    ["texto" => "Busqué a Jehová, y él me oyó, y me libró de todos mis temores.", "cita" => "Salmos 34:4"],
    ["texto" => "Pedid, y se os dará; buscad, y hallaréis; llamad, y se os abrirá.", "cita" => "Mateo 7:7"],
    ["texto" => "El Señor está cerca de todos los que lo invocan, de todos los que lo invocan con sinceridad.", "cita" => "Salmos 145:18"],
    ["texto" => "Perseverad en la oración, velando en ella con acción de gracias.", "cita" => "Colosenses 4:2"],
    ["texto" => "Echa sobre Jehová tu carga, y él te sustentará; no dejará para siempre caído al justo.", "cita" => "Salmos 55:22"],
    ["texto" => "Jehová está conmigo; no temeré lo que me pueda hacer el hombre.", "cita" => "Salmos 118:6"],
    ["texto" => "Todo lo puedo en Cristo que me fortalece.", "cita" => "Filipenses 4:13"],
    ["texto" => "Tú guardarás en completa paz a aquel cuyo pensamiento en ti persevera; porque en ti ha confiado.", "cita" => "Isaías 26:3"],
    ["texto" => "En el día que temo, yo en ti confío.", "cita" => "Salmos 56:3"],
    ["texto" => "Jehová es mi pastor; nada me faltará.", "cita" => "Salmos 23:1"],
    ["texto" => "Orad sin cesar.", "cita" => "1 Tesalonicenses 5:17"],
    ["texto" => "La paz os dejo, mi paz os doy; yo no os la doy como el mundo la da. No se turbe vuestro corazón, ni tenga miedo.", "cita" => "Juan 14:27"],
    ["texto" => "Deléitate asimismo en Jehová, y él te concederá las peticiones de tu corazón.", "cita" => "Salmos 37:4"],
    ["texto" => "Encomienda a Jehová tu camino, y confía en él; y él hará.", "cita" => "Salmos 37:5"],
    ["texto" => "Cercano está Jehová a los quebrantados de corazón; y salva a los contritos de espíritu.", "cita" => "Salmos 34:18"],
    ["texto" => "Dios es nuestro amparo y fortaleza, nuestro pronto auxilio en las tribulaciones.", "cita" => "Salmos 46:1"],
    ["texto" => "Bendito el varón que confía en Jehová, y cuya confianza es Jehová.", "cita" => "Jeremías 17:7"],
    ["texto" => "Y todo lo que pidiereis en oración, creyendo, lo recibiréis.", "cita" => "Mateo 21:22"],
    ["texto" => "Antes que clamen, responderé yo; mientras aún hablan, yo habré oído.", "cita" => "Isaías 65:24"],
    ["texto" => "El que habita al abrigo del Altísimo morará bajo la sombra del Omnipotente.", "cita" => "Salmos 91:1"],
    ["texto" => "Jehová es mi luz y mi salvación; ¿de quién temeré?", "cita" => "Salmos 27:1"],
    ["texto" => "Sáname, oh Jehová, y seré sano; sálvame, y seré salvo; porque tú eres mi alabanza.", "cita" => "Jeremías 17:14"],
    ["texto" => "No temas, porque yo estoy contigo; no desmayes, porque yo soy tu Dios que te esfuerzo.", "cita" => "Isaías 41:10"],
    ["texto" => "Venid a mí todos los que estáis trabajados y cargados, y yo os haré descansar.", "cita" => "Mateo 11:28"],
    ["texto" => "Porque donde están dos o tres congregados en mi nombre, allí estoy yo en medio de ellos.", "cita" => "Mateo 18:20"],
    ["texto" => "Mi Dios, pues, suplirá todo lo que os falta conforme a sus riquezas en gloria en Cristo Jesús.", "cita" => "Filipenses 4:19"],
    ["texto" => "Mas los que esperan a Jehová tendrán nuevas fuerzas; levantarán alas como las águilas.", "cita" => "Isaías 40:31"]
];

$dia_del_ano = (int)date('z');
$indice_versiculo = $dia_del_ano % count($versiculos);
$versiculo_hoy = $versiculos[$indice_versiculo];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Petición de Oración</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f8f6f0;
            color: #5d4037;
            margin: 0;
            padding: 20px 15px;
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
        .logo-container {
            text-align: center;
            margin-bottom: 15px;
        }
        .logo-container img {
            max-width: 130px;
            height: auto;
            display: inline-block;
        }
        h2 {
            margin-top: 0;
            color: #5D4037;
            font-size: 22px;
            text-align: center;
            margin-bottom: 15px;
        }
        .verse-card {
            background-color: #FAF3E0;
            border-left: 4px solid #E8A5B8;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #4A3B32;
        }
        .verse-text {
            font-style: italic;
            margin-bottom: 5px;
        }
        .verse-citation {
            font-weight: bold;
            text-align: right;
            font-size: 13px;
            color: #8C6D62;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success {
            background-color: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
        }
        .alert-error {
            background-color: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 14px;
            color: #5D4037;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #D7CCC8;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s;
            background-color: #FAFAFA;
        }
        input[type="text"]:focus, textarea:focus {
            border-color: #E8A5B8;
            background-color: #FFFFFF;
        }
        textarea {
            resize: vertical;
            min-height: 110px;
        }
        .btn-submit {
            width: 100%;
            background-color: #E8A5B8;
            color: #FFFFFF;
            border: none;
            padding: 14px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-submit:hover {
            background-color: #D894A7;
        }
    </style>
</head>
<body>

<div class="form-card">
    <div class="logo-container">
        <img src="logo.png" alt="Logo de la aplicación">
    </div>

    <h2>Enviar Petición</h2>

    <div class="verse-card">
        <div class="verse-text">"<?php echo htmlspecialchars($versiculo_hoy['texto']); ?>"</div>
        <div class="verse-citation">— <?php echo htmlspecialchars($versiculo_hoy['cita']); ?></div>
    </div>

    <?php if (!empty($mensaje_exito)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($mensaje_exito); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensaje_error)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($mensaje_error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="nombre">Nombre completo:</label>
            <input type="text" id="nombre" name="nombre" placeholder="Tu nombre y apellido" required>
        </div>

        <div class="form-group">
            <label for="peticion">Escribe tu petición:</label>
            <textarea id="peticion" name="peticion" placeholder="Escribe aquí el motivo de oración..." required></textarea>
        </div>

        <button type="submit" name="enviar_peticion" class="btn-submit">Enviar Petición</button>
    </form>
</div>

</body>
</html>
