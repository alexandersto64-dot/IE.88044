<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

if ($_SESSION["rol"] !== "ADMIN") {
    die("Acceso no autorizado.");
}

require_once "../backend/config/database.php";
require_once "../backend/config/admin_seguimiento.php";

$registros = auditoria_listar($conexion, 200);

$etiquetasAccion = [
    "CREAR" => ["texto" => "Creó", "clase" => "is-green"],
    "EDITAR" => ["texto" => "Editó", "clase" => "is-amber"],
    "ELIMINAR" => ["texto" => "Eliminó", "clase" => "is-red"],
    "RESTAURAR" => ["texto" => "Restauró", "clase" => "is-green"],
    "ACTIVAR" => ["texto" => "Activó", "clase" => "is-green"],
    "DESACTIVAR" => ["texto" => "Desactivó", "clase" => "is-amber"],
];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría · Panel de Administración - I.E.P. 88044 Abraham Valdelomar</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">

<header>
    <div>
        <h1><?= icon("history") ?> Registro de auditoría</h1>
        <p>Panel de Administración · I.E.P. 88044 Abraham Valdelomar</p>
    </div>
    <div class="header-actions">
        <a href="../backend/auth/logout.php">Cerrar sesión</a>
    </div>
</header>

<main>

    <p class="placeholder-text">Últimas <?= count($registros) ?> acciones administrativas: quién hizo qué y cuándo, en Usuarios, Alumnos, Cursos y Configuración.</p>

    <?php if (count($registros) === 0): ?>
        <p class="placeholder-text">Todavía no hay acciones registradas.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Quién</th><th>Acción</th><th>Detalle</th><th>Fecha</th></tr></thead>
                <tbody>
                    <?php foreach ($registros as $r): $et = $etiquetasAccion[$r["accion"]] ?? ["texto" => $r["accion"], "clase" => "is-amber"]; ?>
                        <tr>
                            <td><?= htmlspecialchars($r["nombres"] . " " . $r["apellidos"]) ?></td>
                            <td><span class="leyenda-punto <?= $et["clase"] ?>"></span><?= htmlspecialchars($et["texto"]) ?> · <?= htmlspecialchars(ucfirst(strtolower($r["entidad"]))) ?></td>
                            <td><?= htmlspecialchars($r["detalle"]) ?></td>
                            <td><?= htmlspecialchars($r["creado_en"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</main>

</div><!-- /.app-content -->
</div><!-- /.app-shell -->

<script src="../js/panel.js"></script>

</body>
</html>
