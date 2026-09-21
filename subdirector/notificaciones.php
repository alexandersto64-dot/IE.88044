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
require_once "../backend/config/notificaciones.php";

// ==================================================
// NOTIFICACIONES DE SUBDIRECCIÓN
// Misma lógica (listar, contar no leídas, marcar como leídas) que
// ya usa profesor/notificaciones.php, reutilizando las mismas
// funciones de backend/config/notificaciones.php — no se agrega
// ninguna tabla ni consulta nueva, solo esta vista para el rol
// SUBDIRECTOR, que hasta ahora solo veía el contador en el sidebar
// sin poder abrir el listado (ver notificaciones_verificar_modulo_completo
// en backend/config/notificaciones.php, que ya le genera avisos).
// ==================================================

$notificacionesSubdireccion = notificaciones_listar($conexion, (int) $_SESSION["id_usuario"], 20);
$notificacionesNoLeidas = notificaciones_no_leidas($conexion, (int) $_SESSION["id_usuario"]);

if (isset($_GET["ver_notificaciones"])) {
    notificaciones_marcar_leidas($conexion, (int) $_SESSION["id_usuario"]);
    header("Location: notificaciones.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones · Panel del Subdirector - I.E.P. 88044 Abraham Valdelomar</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">


<header>
    <div>
        <h1>Notificaciones</h1>
        <p>Panel del Subdirector · I.E.P. 88044 Abraham Valdelomar</p>
    </div>
    <div class="header-actions">
        <a href="../backend/auth/logout.php">Cerrar sesión</a>
    </div>
</header>


<main>

    <section class="panel-section">

        <div class="panel-section-head">
            <h2>
                <?= icon("bell") ?> Notificaciones
                <?php if ($notificacionesNoLeidas > 0): ?>
                    <span class="status-badge status-requiere_cambios"><?= $notificacionesNoLeidas ?> nueva(s)</span>
                <?php endif; ?>
            </h2>
        </div>

        <?php if (count($notificacionesSubdireccion) === 0): ?>

            <p class="placeholder-text">No hay notificaciones todavía.</p>

        <?php else: ?>

            <?php foreach ($notificacionesSubdireccion as $n): ?>
                <div class="notif-item <?= $n["leido"] ? "leida" : "" ?>">
                    <?php if (!empty($n["url"])): ?>
                        <a href="<?= htmlspecialchars($n["url"]) ?>"><?= htmlspecialchars($n["mensaje"]) ?></a>
                    <?php else: ?>
                        <?= htmlspecialchars($n["mensaje"]) ?>
                    <?php endif; ?>
                    <span class="placeholder-text"> · <?= htmlspecialchars($n["creado_en"]) ?></span>
                </div>
            <?php endforeach; ?>

            <?php if ($notificacionesNoLeidas > 0): ?>
                <p><a href="notificaciones.php?ver_notificaciones=1">Marcar todas como leídas</a></p>
            <?php endif; ?>

        <?php endif; ?>

    </section>

</main>


</div><!-- /.app-content -->
</div><!-- /.app-shell -->

<script src="../js/panel.js"></script>

</body>
</html>
