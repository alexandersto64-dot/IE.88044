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

$totalUsuarios = (int)$conexion->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalActivos = (int)$conexion->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'ACTIVO'")->fetchColumn();
$totalProfesores = (int)$conexion->query("SELECT COUNT(*) FROM profesores")->fetchColumn();
$totalCursos = (int)$conexion->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
$totalDocumentos = (int)$conexion->query("SELECT COUNT(*) FROM documentos")->fetchColumn();
$totalTrabajos = (int)$conexion->query("SELECT COUNT(*) FROM trabajos")->fetchColumn();

$usuariosPorRol = $conexion->query("
    SELECT r.nombre AS rol, COUNT(*) AS total
    FROM usuarios u
    INNER JOIN roles r ON u.id_rol = r.id_rol
    GROUP BY r.nombre
")->fetchAll();

$trabajosPorEstado = $conexion->query("
    SELECT estado, COUNT(*) AS total
    FROM trabajos
    GROUP BY estado
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes · Panel de Administración - I.E.P. 88044 Abraham Valdelomar</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">


<header>
    <div>
        <h1>Reportes del sistema</h1>
        <p>Panel de Administración · I.E.P. 88044 Abraham Valdelomar</p>
    </div>
    <div class="header-actions">
        <a href="../backend/auth/logout.php">Cerrar sesión</a>
    </div>
</header>

<main>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-value"><?= $totalUsuarios ?></span>
            <span class="stat-label">Usuarios totales</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalActivos ?></span>
            <span class="stat-label">Usuarios activos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalProfesores ?></span>
            <span class="stat-label">Profesores</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalCursos ?></span>
            <span class="stat-label">Cursos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalDocumentos ?></span>
            <span class="stat-label">Documentos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalTrabajos ?></span>
            <span class="stat-label">Trabajos asignados</span>
        </div>
    </div>

    <h2>Usuarios por rol</h2>
    <?php if (count($usuariosPorRol) === 0): ?>
        <p class="placeholder-text">Sin datos todavía.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Rol</th><th>Cantidad</th></tr></thead>
                <tbody>
                    <?php foreach ($usuariosPorRol as $r): ?>
                        <tr><td><span class="role-badge"><?= htmlspecialchars($r['rol']) ?></span></td><td><?= (int)$r['total'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2>Trabajos por estado</h2>
    <?php if (count($trabajosPorEstado) === 0): ?>
        <p class="placeholder-text">Sin datos todavía.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Estado</th><th>Cantidad</th></tr></thead>
                <tbody>
                    <?php foreach ($trabajosPorEstado as $t): ?>
                        <tr><td><?= htmlspecialchars($t['estado']) ?></td><td><?= (int)$t['total'] ?></td></tr>
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
