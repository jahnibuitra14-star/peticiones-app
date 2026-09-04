<?php
// Configurar zona horaria oficial de Venezuela en PHP
date_default_timezone_set('America/Caracas');

// Configuración para ocultar errores técnicos al usuario
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

$mensaje_exito = "";
$mensaje_error = "";

// LISTA DE VERSÍCULOS BÍBLICOS DE CONSUELO Y FE PARA LAS MADRES
$versiculos = [
    ["texto" => "Clama a mí, y yo te responderé, y te enseñaré cosas grandes y ocultas que tú no conoces.", "cita" => "Jeremías 33:3"],
    ["texto" => "Por este niño oraba, y el Señor me otorgó lo que le pedí.", "cita" => "1 Samuel 1:27"],
    ["texto" => "Instruye al niño en su camino, y aun cuando fuere viejo no se apartará de él.", "cita" => "Proverbios 22:6"],
    ["texto" => "Los que siembran con lágrimas, con regocijo cosecharán.", "cita" => "Salmo 126:5"],
    ["texto" => "Así dice el Señor: Reprime tu llanto y enjuga tus lágrimas, porque tu trabajo tendrá su recompensa... ¡Tus hijos volverán a su propia tierra!", "cita" => "Jeremías 31:16-17"],
    ["texto" => "Echa sobre el Señor tu carga, y él te sostendrá; no dejará para siempre caído al justo.", "cita" => "Salmo 55:22"],
    ["texto" => "Creo que veré la bondad del Señor en la tierra de los vivientes. Aguarda al Señor; esfuérzate, y aliéntese tu corazón.", "cita" => "Salmo 27:13-14"],
    ["texto" => "Creed en el Señor Jesucristo, y serás salvo, tú y tu casa.", "cita" => "Hechos 16:31"],
    ["texto" => "El Señor peleará por vosotros, y vosotros estaréis tranquilos.", "cita" => "Éxodo 14:14"],
    ["texto" => "Y todos tus hijos serán enseñados por el Señor; y se multiplicará la paz de tus hijos.", "cita" => "Isaías 54:13"],
    ["texto" => "No temas, porque yo estoy contigo; no desmayes, porque yo soy tu Dios que te esfuerzo; siempre te ayudaré.", "cita" => "Isaías 41:10"],
    ["texto" => "Porque yo sé los pensamientos que tengo acerca de vosotros, dice el Señor, pensamientos de paz, y no de mal, para daros el fin que esperáis.", "cita" => "Jeremías 29:11"],
    ["texto" => "Derramaré mi Espíritu sobre tu descendencia, y mi bendición sobre tus renuevos.", "cita" => "Isaías 44:3"],
    ["texto" => "La paz os dejo, mi paz os doy; yo no os la doy como el mundo la da. No se turbe vuestro corazón, ni tenga miedo.", "cita" => "Juan 14:27"],
    ["texto" => "Por nada estéis afanosos, sino sean conocidas vuestras peticiones delante de Dios en toda oración y ruego, con acción de gracias.", "cita" => "Filipenses 4:6"],
    ["texto" => "Bendito sea el Dios y Padre de nuestro Señor Jesucristo... que nos consuela en todas nuestras tribulaciones.", "cita" => "2 Corintios 1:3-4"],
    ["texto" => "He aquí, herencia del Señor son los hijos; cosa de estima el fruto del vientre.", "cita" => "Salmo 127:3"],
    ["texto" => "Generación a generación celebrará tus obras, y anunciará tus poderosos hechos.", "cita" => "Salmo 145:4"],
    ["texto" => "El Señor es mi pastor; nada me faltará. En lugares de delicados pastos me hará descansar.", "cita" => "Salmo 23:1-2"],
    ["texto" => "Ciertamente el bien y la misericordia me seguirán todos los días de mi vida.", "cita" => "Salmo 23:6"],
    ["texto" => "El Dios de esperanza os llene de todo gozo y paz en la fe, para que abundéis en esperanza por el poder del Espíritu Santo.", "cita" => "Romanos 15:13"],
    ["texto" => "Venid a mí todos los que estáis trabajados y cargados, y yo os haré descansar.", "cita" => "Mateo 11:28"],
    ["texto" => "Torre fuerte es el nombre del Señor; a él correrá el justo, y levantado será.", "cita" => "Proverbios 18:10"],
    ["texto" => "El Señor guardará tu salida y tu entrada desde ahora y para siempre.", "cita" => "Salmo 121:8"],
    ["texto" => "Fízome descansar en verdes pastos; junto a aguas de reposo me pastoreará.", "cita" => "Salmo 23:2"],
    ["texto" => "Perseverad en la oración, velando en ella con acción de gracias.", "cita" => "Colosenses 4:2"],
    ["texto" => "Si puedes creer, al que cree todo le es posible.", "cita" => "Marcos 9:23"],
    ["texto" => "Mas la misericordia del Señor es desde la eternidad y hasta la eternidad sobre los que le temen, y su justicia sobre los hijos de los hijos.", "cita" => "Salmo 103:17"],
    ["texto" => "La oración eficaz del justo puede mucho.", "cita" => "Santiago 5:16"],
    ["texto" => "El Señor te bendiga, y te guarde; el Señor haga resplandecer su rostro sobre ti, y tenga de ti misericordia.", "cita" => "Números 6:24-25"],
    ["texto" => "Y esta es la confianza que tenemos en él, que si pedimos alguna cosa conforme a su voluntad, él nos oye.", "cita" => "1 Juan 5:14"]
];

// Seleccionar versículo según el día del año para que vaya rotando automáticamente
$dia_del_ano = (int)date('z'); 
$indice_versiculo = $dia_del_ano % count($versiculos);
$versiculo_hoy = $versiculos[$indice_versiculo];

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
        h2 {
            margin-top: 0;
            color: #5D4037;
            font-size: 22px;
            text-align: center;
            margin-bottom: 15px;
        }
        /* ESTILO PARA LA TARJETA DEL VERSÍCULO */
        .tarjeta-versiculo {
            background-color: #FAF0E6;
            border-left: 4px solid #F48FB1;
            padding: 15px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 22px;
            font-style: italic;
        }
        .texto-versiculo {
            color: #5D4037;
            font-size: 14px;
            line-height: 1.5;
            margin: 0 0 6px 0;
        }
        .cita-versiculo {
            color: #8D6E63;
            font-weight: bold;
            font-size: 12px;
            text-align: right;
            margin: 0;
            font-style: normal;
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

    <!-- BLOQUE DEL VERSÍCULO DEL DÍA -->
    <div class="tarjeta-versiculo">
        <p class="texto-versiculo">“<?php echo htmlspecialchars($versiculo_hoy['texto']); ?>”</p>
        <p class="cita-versiculo">— <?php echo htmlspecialchars($versiculo_hoy['cita']); ?></p>
    </div>

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
