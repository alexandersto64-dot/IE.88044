<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

if ($_SESSION["rol"] !== "SUBDIRECTOR") {
    die("Acceso no autorizado.");
}

require_once "../backend/config/database.php";

$totalProfesores = (int)$conexion->query("SELECT COUNT(*) FROM profesores")->fetchColumn();
$totalDocumentos = (int)$conexion->query("SELECT COUNT(*) FROM documentos")->fetchColumn();
$totalComunicados = (int)$conexion->query("SELECT COUNT(*) FROM comunicados")->fetchColumn();
$totalSolicitudesPendientes = (int)$conexion->query("SELECT COUNT(*) FROM solicitudes WHERE estado = 'PENDIENTE'")->fetchColumn();

$solicitudesPorEstado = $conexion->query("
    SELECT estado, COUNT(*) AS total FROM solicitudes GROUP BY estado
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes · Panel del Subdirector - I.E.P. 88044 Abraham Valdelomar</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">


<header>
    <div>
        <h1>Reportes institucionales</h1>
        <p>Panel del Subdirector · I.E.P. 88044 Abraham Valdelomar</p>
    </div>
    <div class="header-actions">
        <a href="../backend/auth/logout.php">Cerrar sesión</a>
    </div>
</header>

<main>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-value"><?= $totalProfesores ?></span>
            <span class="stat-label">Profesores</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalDocumentos ?></span>
            <span class="stat-label">Documentos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalComunicados ?></span>
            <span class="stat-label">Comunicados</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalSolicitudesPendientes ?></span>
            <span class="stat-label">Solicitudes pendientes</span>
        </div>
    </div>

    <h2>Solicitudes por estado</h2>
    <?php if (count($solicitudesPorEstado) === 0): ?>
        <p class="placeholder-text">Sin datos todavía.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Estado</th><th>Cantidad</th></tr></thead>
                <tbody>
                    <?php foreach ($solicitudesPorEstado as $s): ?>
                        <tr><td><?= htmlspecialchars($s['estado']) ?></td><td><?= (int)$s['total'] ?></td></tr>
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
