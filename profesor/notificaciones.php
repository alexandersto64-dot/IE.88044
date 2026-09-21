<?php

require_once __DIR__ . "/../backend/partials/profesor_bootstrap.php";

// ==================================================
// NOTIFICACIONES
// Misma lógica (listar, contar no leídas, marcar como leídas) que
// antes vivía en dashboard.php, solo movida a su propia página.
// ==================================================

$notificacionesProfesor = notificaciones_listar($conexion, $_SESSION["id_usuario"], 5);
$notificacionesNoLeidas = notificaciones_no_leidas($conexion, $_SESSION["id_usuario"]);

if (isset($_GET["ver_notificaciones"])) {
    notificaciones_marcar_leidas($conexion, $_SESSION["id_usuario"]);
    header("Location: notificaciones.php");
    exit;
}

$gradosAsignados = profesor_grados_asignados($conexion, $profesor["id_profesor"]);

?>



<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Notificaciones - I.E.P. 88044 Abraham Valdelomar
    </title>

    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">

</head>


<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">


<header>

    <div>
        <span class="header-eyebrow">Cuenta</span>
        <h1>Notificaciones</h1>
    </div>

    <div class="header-actions">
        <div class="header-identity">
            <div class="header-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($profesor["nombres"], 0, 1)) . mb_strtoupper(mb_substr($profesor["apellidos"], 0, 1))) ?></div>
            <div class="header-identity-text">
                <strong><?= htmlspecialchars($profesor["nombres"]) ?> <?= htmlspecialchars($profesor["apellidos"]) ?></strong>
                <span>I.E.P. 88044</span>
            </div>
        </div>
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

        <?php if (count($notificacionesProfesor) === 0): ?>

            <p class="placeholder-text">No tienes notificaciones todavía.</p>

        <?php else: ?>

            <?php foreach ($notificacionesProfesor as $n): ?>
                <div class="notif-item <?= $n["leido"] ? "leida" : "" ?>">
                    <?= htmlspecialchars($n["mensaje"]) ?>
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
