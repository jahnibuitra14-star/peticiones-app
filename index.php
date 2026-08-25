<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rajel-Amishav - Enviar Petición</title>
    <?php date_default_timezone_set('America/Caracas'); ?>
    <style>
        body { 
            font-family: system-ui, -apple-system, sans-serif; 
            max-width: 480px; 
            margin: 0 auto; 
            padding: 30px 20px; 
            background-color: #FDF6EC; 
            /* MARCA DE AGUA Y FONDO BEIGE */
            background-image: linear-gradient(rgba(253, 246, 236, 0.92), rgba(253, 246, 236, 0.92)), url('logo.png');
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            background-attachment: fixed;
            color: #5D4037;
        }
        .card { 
            background-color: #FFFFFF; 
            border: 1px solid #F3E5DC; 
            border-radius: 16px; 
            padding: 25px 30px 30px 30px; 
            box-shadow: 0 8px 25px rgba(93, 64, 55, 0.08); 
        }
        .logo-container {
            text-align: center;
            margin-bottom: 15px;
        }
        .logo-container img {
            max-width: 200px;
            height: auto;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(93, 64, 55, 0.12);
        }
        h2 {
            margin-top: 10px;
            margin-bottom: 20px;
            text-align: center;
            color: #5D4037;
            font-weight: 700;
            font-size: 20px;
        }
        .form-group { margin-bottom: 18px; }
        label { 
            display: block; 
            margin-bottom: 7px; 
            font-weight: 600; 
            color: #5D4037; 
            font-size: 15px;
        }
        input, textarea { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #E6D7CF; 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 15px; 
            background-color: #FFF;
            color: #5D4037;
            transition: all 0.2s;
        }
        input:focus, textarea:focus { 
            outline: none; 
            border-color: #F8BBD0; 
            box-shadow: 0 0 0 3px rgba(248, 187, 208, 0.3);
        }
        button { 
            background-color: #F48FB1; 
            color: white; 
            padding: 14px; 
            border: none; 
            border-radius: 8px; 
            width: 100%; 
            font-weight: 700; 
            cursor: pointer; 
            font-size: 16px; 
            transition: background-color 0.2s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        button:hover { 
            background-color: #F06292; 
        }
        .mensaje { 
            margin-bottom: 20px; 
            padding: 12px; 
            border-radius: 8px; 
            font-size: 14px; 
            text-align: center;
            font-weight: 600;
        }
        .exito { 
            background-color: #E8F5E9; 
            color: #2E7D32; 
            border: 1px solid #C8E6C9;
        }
        .error { 
            background-color: #FFEBEE; 
            color: #C62828; 
            border: 1px solid #FFCDD2;
        }
    </style>
</head>
<body>
    <div class="card">
        <!-- LOGO ENCABEZADO -->
        <div class="logo-container">
            <img src="logo.png" alt="Rajel Amishav Logo">
        </div>

        <h2>Registro de Petición</h2>

        <?php
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            // CÓDIGO NUEVO (VARIABLES DE ENTORNO RAILWAY):
$host = getenv('MYSQLHOST') ?: 'localhost';
$db   = getenv('MYSQLDATABASE') ?: 'railway';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: '3306';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass);

                $nombre   = trim($_POST["nombre"] ?? '');
                $peticion = trim($_POST["peticion"] ?? '');

                if (!empty($nombre) && !empty($peticion)) {
                    $stmt = $pdo->prepare("INSERT INTO registros (nombre, peticion) VALUES (:nombre, :peticion)");
                    $stmt->execute([
                        ':nombre'   => $nombre,
                        ':peticion' => $peticion
                    ]);
                    echo '<div class="mensaje exito">✓ Petición enviada correctamente. ¡Dios te bendiga!</div>';
                } else {
                    echo '<div class="mensaje error">Por favor, completa todos los campos.</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="mensaje error">Error de conexión. Inténtalo más tarde.</div>';
            }
        }
        ?>

        <form method="POST">
            <div class="form-group">
                <label for="nombre">Nombre completo:</label>
                <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" required>
            </div>
            <div class="form-group">
                <label for="peticion">Escribe tu petición:</label>
                <textarea id="peticion" name="peticion" rows="5" placeholder="Escribe aquí los detalles..." required></textarea>
            </div>
            <button type="submit">Enviar Petición</button>
        </form>
    </div>
</body>
</html>
