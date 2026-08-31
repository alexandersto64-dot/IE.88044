<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

if ($_SESSION["rol"] !== "PROFESOR") {
    die("Acceso no autorizado.");
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos Institucionales · Panel del Profesor - I.E.P. 88044 Abraham Valdelomar</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">

<header>
    <div>
        <h1>Documentos Institucionales</h1>
        <p>Panel del Profesor · I.E.P. 88044 Abraham Valdelomar</p>
    </div>
    <div class="header-actions">
        <a href="dashboard.php" class="btn-secondary">← Mi panel</a>
        <a href="../backend/auth/logout.php">Cerrar sesión</a>
    </div>
</header>

<main>
    <div class="empty-state">
        <span class="empty-state-icon" aria-hidden="true">🚧</span>
        <h2>Documentos Institucionales — en preparación</h2>
        <p>
            Esta sección quedará dividida en 3 categorías. Los nombres y el contenido de
            esas categorías todavía no fueron definidos, así que no se muestran aquí para
            evitar mostrar datos inventados. Se habilitarán en cuanto se indique cuáles son.
        </p>
    </div>
</main>

</div><!-- /.app-content -->
</div><!-- /.app-shell -->

<script src="../js/panel.js"></script>

</body>
</html>
