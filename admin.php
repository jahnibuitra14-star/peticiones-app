<?php
// Configurar zona horaria oficial de Venezuela
date_default_timezone_set('America/Caracas');

// Cambia la línea de conexión PDO para incluir el puerto correctamente:
$host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: '127.0.0.1';
$db   = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'railway';
$user = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: '3306';

$pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);

$todos_los_registros = [];
$registros_por_fecha = [];

try {
   
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
    
    // TRAE TODOS LOS REGISTROS USA LA COLUMNA CORRECTA: fecha_registro
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
    <title>Historial de Peticiones - Panel</title>
    <style>
        body { 
            font-family: system-ui, -apple-system, sans-serif; 
            padding: 40px 20px; 
            max-width: 950px; 
            margin: 0 auto; 
            color: #5D4037; 
            background-color: #FDF6EC;
        }
        .main-container {
            background-color: #FFFFFF;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #F3E5DC;
            box-shadow: 0 4px 15px rgba(93, 64, 55, 0.05);
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
            padding-bottom: 15px;
            border-bottom: 2px solid #FDF6EC;
        }
        .acciones-header {
            display: flex;
            gap: 10px;
        }
        h2 { margin: 0; color: #5D4037; font-weight: 700; }
        
        .titulo-fecha {
            margin-top: 30px;
            margin-bottom: 12px;
            color: #8D6E63;
            font-size: 16px;
            font-weight: 700;
            border-bottom: 2px solid #FAF0E6;
            padding-bottom: 5px;
            text-transform: capitalize;
        }

        .btn-imprimir { 
            background-color: #F48FB1; 
            color: white; 
            padding: 10px 18px; 
            border: none; 
            border-radius: 8px; 
            font-weight: 700; 
            cursor: pointer; 
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: background-color 0.2s;
        }
        .btn-imprimir:hover { 
            background-color: #F06292; 
        }

        .btn-eliminar-todo {
            background-color: #E57373;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: background-color 0.2s;
        }
        .btn-eliminar-todo:hover {
            background-color: #D32F2F;
        }
        
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

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 5px; 
            margin-bottom: 20px;
            background-color: #FFF;
        }
        th, td { 
            border: 1px solid #E6D7CF; 
            padding: 14px 16px; 
            text-align: left; 
            font-size: 15px;
        }
        th { 
            background-color: #FAF0E6; 
            font-weight: 700; 
            color: #5D4037;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        tr.fila-peticion {
            cursor: context-menu;
            user-select: text;
        }
        tr.fila-peticion:nth-child(even) td {
            background-color: #FDFBF9;
        }
        tr.fila-peticion:hover td {
            background-color: #FFF0F5;
        }
        .hora { color: #8D6E63; font-weight: 600; }
        .nombre { color: #5D4037; font-weight: 700; }
        .peticion { line-height: 1.5; color: #6D4C41; }

        /* MENÚ CONTEXTUAL (CLIC DERECHO) */
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
        #context-menu button:hover {
            background-color: #FFEBEE;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { 
                padding: 0; 
                background-color: white !important; 
                color: black !important;
            }
            .main-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                background-color: white !important;
            }
            table { 
                border: 1px solid #000 !important; 
                background-color: white !important;
            }
            th, td { 
                border: 1px solid #000 !important; 
                padding: 10px !important;
                color: black !important;
            }
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
                <?php if (count($todos_los_registros) > 0): ?>
                    <form method="POST" style="margin:0;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar TODO el historial de peticiones?');">
                        <input type="hidden" name="action" value="eliminar_todo">
                        <button type="submit" class="btn-eliminar-todo">🗑️ Borrar Historial</button>
                    </form>
                <?php endif; ?>
                <button class="btn-imprimir" onclick="window.print()">🖨️ Imprimir Registros</button>
            </div>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] === 'deleted'): ?>
                <div class="alerta-exito no-print">✓ Petición eliminada correctamente.</div>
            <?php elseif ($_GET['status'] === 'all_deleted'): ?>
                <div class="alerta-exito no-print">✓ Se han eliminado todas las peticiones correctamente.</div>
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
                <table>
                    <thead>
                        <tr>
                            <th style="width: 12%;">Hora</th>
                            <th style="width: 28%;">Nombre</th>
                            <th style="width: 60%;">Petición</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($peticiones_dia as $row): ?>
                            <tr class="fila-peticion" data-id="<?php echo $row['id']; ?>">
                                <td class="hora"><?php echo date("H:i", strtotime($row['fecha_registro'])); ?></td>
                                <td class="nombre"><?php echo htmlspecialchars($row['nombre']); ?></td>
                                <td class="peticion"><?php echo nl2br(htmlspecialchars($row['peticion'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php else: ?>
            <table>
                <tbody>
                    <tr>
                        <td style="text-align: center; color: #8D6E63; padding: 30px;">No se han recibido peticiones hasta el momento.</td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- FORMULARIO OCULTO Y MENÚ CONTEXTUAL -->
    <form id="form-eliminar-individual" method="POST" style="display:none;">
        <input type="hidden" name="action" value="eliminar_uno">
        <input type="hidden" name="id" id="id-para-eliminar" value="">
    </form>

    <div id="context-menu">
        <button id="btn-context-eliminar">🗑️ Eliminar petición</button>
    </div>

    <script>
        const menu = document.getElementById('context-menu');
        const formEliminar = document.getElementById('form-eliminar-individual');
        const inputId = document.getElementById('id-para-eliminar');
        let idSeleccionado = null;

        // Mostrar menú al hacer clic derecho en cualquier fila
        document.querySelectorAll('.fila-peticion').forEach(row => {
            row.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                idSeleccionado = this.getAttribute('data-id');
                
                menu.style.left = e.pageX + 'px';
                menu.style.top = e.pageY + 'px';
                menu.style.display = 'block';
            });
        });

        // Ocultar menú al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!menu.contains(e.target)) {
                menu.style.display = 'none';
            }
        });

        // Ejecutar eliminación
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
