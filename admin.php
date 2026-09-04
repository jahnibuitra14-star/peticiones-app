<?php
// Configurar zona horaria oficial de Venezuela en PHP
date_default_timezone_set('America/Caracas');

// DEFINIR PIN DE SEGURIDAD PARA LA LIMPIEZA
define('ADMIN_PIN', '1234');

$todos_los_registros = [];
$registros_por_fecha = [];

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

    // LÓGICA PARA ELIMINAR PETICIONES DEL MES PASADO (CON PIN DE SEGURIDAD)
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'eliminar_mes_pasado') {
        $pin_ingresado = trim($_POST['admin_pin'] ?? '');

        if ($pin_ingresado === ADMIN_PIN) {
            $primer_dia_mes_actual = date('Y-m-01 00:00:00');
            $stmt_delete_old = $pdo->prepare("DELETE FROM registros WHERE fecha_registro < :fecha_corte");
            $stmt_delete_old->execute([':fecha_corte' => $primer_dia_mes_actual]);
            
            $filas_borradas = $stmt_delete_old->rowCount();
            header("Location: admin.php?status=month_deleted&count=" . $filas_borradas);
            exit;
        } else {
            header("Location: admin.php?status=invalid_pin");
            exit;
        }
    }

    // TRAE TODOS LOS REGISTROS
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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Peticiones - Panel</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: system-ui, -apple-system, sans-serif; 
            padding: 20px 10px; 
            max-width: 950px; 
            margin: 0 auto; 
            color: #5D4037; 
            background-color: #FDF6EC;
        }
        .main-container {
            background-color: #FFFFFF;
            padding: 20px 15px;
            border-radius: 12px;
            border: 1px solid #F3E5DC;
            box-shadow: 0 4px 15px rgba(93, 64, 55, 0.05);
            width: 100%;
            overflow: hidden;
        }
        .header { 
            display: flex; 
            flex-wrap: wrap; 
            justify-content: space-between; 
            align-items: center; 
            gap: 15px;
            margin-bottom: 25px; 
            padding-bottom: 15px;
            border-bottom: 2px solid #FDF6EC;
        }
        .acciones-header {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            width: 100%;
        }
        h2 { margin: 0; color: #5D4037; font-weight: 700; font-size: 20px; }
        
        .titulo-fecha {
            margin-top: 25px;
            margin-bottom: 12px;
            color: #8D6E63;
            font-size: 15px;
            font-weight: 700;
            border-bottom: 2px solid #FAF0E6;
            padding-bottom: 5px;
        }

        .btn-imprimir, .btn-eliminar-todo { 
            flex: 1;
            text-align: center;
            padding: 10px 12px; 
            border: none; 
            border-radius: 8px; 
            font-weight: 700; 
            cursor: pointer; 
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .btn-imprimir { background-color: #F48FB1; color: white; }
        .btn-imprimir:hover { background-color: #F06292; }
        .btn-eliminar-todo { background-color: #E57373; color: white; }
        .btn-eliminar-todo:hover { background-color: #D32F2F; }
        
        .alerta-exito {
            background-color: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
        }
        .alerta-error {
            background-color: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
        }

        /* ESTILOS DISCRETOS PARA LIMPIEZA DE MES ANTERIOR */
        .admin-limpieza-section {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #E0D7D3;
            text-align: center;
        }
        .btn-toggle-limpieza {
            background: none;
            border: none;
            color: #A1887F;
            font-size: 12px;
            cursor: pointer;
            text-decoration: underline;
        }
        .btn-toggle-limpieza:hover { color: #5D4037; }
        .box-limpieza {
            display: none;
            margin: 12px auto 0 auto;
            max-width: 320px;
            background-color: #FAFAFA;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #E0E0E0;
        }
        .input-pin {
            width: 100%;
            padding: 8px;
            font-size: 13px;
            border: 1px solid #D7CCC8;
            border-radius: 6px;
            margin-bottom: 8px;
            text-align: center;
        }

        /* Contenedor responsivo para la tabla */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 5px; 
            margin-bottom: 20px;
            background-color: #FFF;
            min-width: 500px;
        }
        th, td { 
            border: 1px solid #E6D7CF; 
            padding: 10px 12px; 
            text-align: left; 
            font-size: 14px;
        }
        th { 
            background-color: #FAF0E6; 
            font-weight: 700; 
            color: #5D4037;
            text-transform: uppercase;
            font-size: 12px;
        }
        tr.fila-peticion {
            cursor: context-menu;
            user-select: text;
        }
        tr.fila-peticion:nth-child(even) td { background-color: #FDFBF9; }
        tr.fila-peticion:hover td { background-color: #FFF0F5; }
        .hora { color: #8D6E63; font-weight: 600; white-space: nowrap; }
        .nombre { color: #5D4037; font-weight: 700; }
        .peticion { line-height: 1.4; color: #6D4C41; }

        #context-menu {
            display: none;
            position: absolute;
            z-index: 1000;
            background-color: #FFFFFF;
            border: 1px solid #F3E5DC;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(93, 64, 55, 0.15);
            padding: 5px 0;
            min-width: 160px;
        }
        #context-menu button {
            width: 100%;
            background: none;
            border: none;
            padding: 10px 15px;
            text-align: left;
            color: #D32F2F;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }
        #context-menu button:hover { background-color: #FFEBEE; }

        /* Pantallas grandes */
        @media (min-width: 600px) {
            body { padding: 40px 20px; }
            .main-container { padding: 30px; }
            .acciones-header { width: auto; }
            .btn-imprimir { flex: none; font-size: 13px; padding: 10px 18px; }
            h2 { font-size: 22px; }
            th, td { padding: 14px 16px; font-size: 15px; }
            table { min-width: 100%; }
        }
        
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background-color: white !important; color: black !important; }
            .main-container { box-shadow: none !important; border: none !important; padding: 0 !important; background-color: white !important; }
            table { border: 1px solid #000 !important; background-color: white !important; min-width: 100% !important; }
            th, td { border: 1px solid #000 !important; padding: 10px !important; color: black !important; }
            th { background-color: #eee !important; }
            tr.fila-peticion:nth-child(even) td { background-color: white !important; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header no-print">
            <h2>Historial de Peticiones</h2>
            <div class="acciones-header">
                <button class="btn-imprimir" onclick="window.print()">🖨️ Imprimir Registros</button>
            </div>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] === 'deleted'): ?>
                <div class="alerta-exito no-print">✓ Petición eliminada correctamente.</div>
            <?php elseif ($_GET['status'] === 'month_deleted'): ?>
                <div class="alerta-exito no-print">✓ Se han eliminado <?php echo (int)($_GET['count'] ?? 0); ?> petición(es) del mes pasado.</div>
            <?php elseif ($_GET['status'] === 'invalid_pin'): ?>
                <div class="alerta-error no-print">✕ PIN de administración incorrecto.</div>
            <?php endif; ?>
            <script>
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.pathname);
                }
            </script>
        <?php endif; ?>

        <?php if (count($registros_por_fecha) > 0): ?>
            <?php foreach ($registros_por_fecha as $fecha => $peticiones_dia): ?>
                <div class="titulo-fecha">
                    📅 Peticiones del <?php echo date("d/m/Y", strtotime($fecha)); ?>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 15%;">Hora</th>
                                <th style="width: 30%;">Nombre</th>
                                <th style="width: 55%;">Petición</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($peticiones_dia as $row): ?>
                                <tr class="fila-peticion" data-id="<?php echo (int)$row['id']; ?>">
                                    <td class="hora"><?php echo date("H:i", strtotime($row['fecha_registro'])); ?></td>
                                    <td class="nombre"><?php echo htmlspecialchars($row['nombre']); ?></td>
                                    <td class="peticion"><?php echo nl2br(htmlspecialchars($row['peticion'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <tbody>
                        <tr>
                            <td style="text-align: center; color: #8D6E63; padding: 30px;">No se han recibido peticiones hasta el momento.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- SECCIÓN DISCRETA EN EL PIE PARA ELIMINAR MES PASADO -->
        <div class="admin-limpieza-section no-print">
            <button type="button" class="btn-toggle-limpieza" onclick="toggleLimpiezaBox()">Limpieza del mes pasado</button>
            <div id="boxLimpieza" class="box-limpieza">
                <form method="POST" action="admin.php">
                    <input type="hidden" name="action" value="eliminar_mes_pasado">
                    <p style="font-size: 11px; color: #757575; margin: 0 0 8px 0;">Borra peticiones anteriores al primer día del mes actual.</p>
                    <input type="password" name="admin_pin" class="input-pin" placeholder="Ingresa PIN de seguridad" required>
                    <button type="submit" class="btn-eliminar-todo" style="width:100%;" onclick="return confirm('¿Confirmas eliminar las peticiones del mes pasado?');">Borrar mes anterior</button>
                </form>
            </div>
        </div>
    </div>

    <form id="form-eliminar-individual" method="POST" action="admin.php" style="display:none;">
        <input type="hidden" name="action" value="eliminar_uno">
        <input type="hidden" name="id" id="id-para-eliminar" value="">
    </form>

    <div id="context-menu">
        <button id="btn-context-eliminar">🗑️ Eliminar petición</button>
    </div>

    <script>
        // Lógica para toggle del menú discreto
        function toggleLimpiezaBox() {
            var box = document.getElementById('boxLimpieza');
            box.style.display = (box.style.display === 'block') ? 'none' : 'block';
        }

        // Lógica intacta para eliminar individual por clic derecho
        const menu = document.getElementById('context-menu');
        const formEliminar = document.getElementById('form-eliminar-individual');
        const inputId = document.getElementById('id-para-eliminar');
        let idSeleccionado = null;

        document.querySelectorAll('.fila-peticion').forEach(row => {
            row.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                idSeleccionado = this.getAttribute('data-id');
                
                menu.style.left = e.pageX + 'px';
                menu.style.top = e.pageY + 'px';
                menu.style.display = 'block';
            });
        });

        document.addEventListener('click', function(e) {
            if (!menu.contains(e.target)) {
                menu.style.display = 'none';
            }
        });

        document.getElementById('btn-context-eliminar').addEventListener('click', function() {
            if (idSeleccionado && confirm('¿Deseas eliminar esta petición?')) {
                inputId.value = idSeleccionado;
                formEliminar.submit();
            }
            menu.style.display = 'none';
        });
    </script>
</body>
</html>
