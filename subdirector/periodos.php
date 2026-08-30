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
require_once "../backend/config/seguridad_csrf.php";
require_once "../backend/config/flash.php";
[$mensaje, $mensajeTipo] = flash_get();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_verificar();

    $nombre = trim($_POST["nombre"] ?? "");

    if ($nombre === "") {

        $mensaje = "El nombre del periodo es obligatorio.";
        $mensajeTipo = "error";

    } else {

        try {

            $stmt = $conexion->prepare("INSERT INTO periodos_academicos (nombre) VALUES (?)");
            $stmt->execute([$nombre]);

            $mensaje = "Periodo académico agregado correctamente.";
            $mensajeTipo = "success";

        } catch (PDOException $e) {

            $mensaje = "No se pudo agregar el periodo académico.";
            $mensajeTipo = "error";

        }

    }

    // Evita el reenvío del formulario al recargar (PRG)
    if ($mensaje !== null) {
        flash_set($mensaje, $mensajeTipo);
    }
    header("Location: periodos.php");
    exit;

}

$periodos = $conexion->query("SELECT id_periodo, nombre FROM periodos_academicos ORDER BY id_periodo DESC")->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Periodos académicos · Panel del Subdirector - I.E.P. 88044 Abraham Valdelomar</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">


<header>
    <div>
        <h1>Periodos académicos</h1>
        <p>Panel del Subdirector · I.E.P. 88044 Abraham Valdelomar</p>
    </div>
    <div class="header-actions">
        <a href="../backend/auth/logout.php">Cerrar sesión</a>
    </div>
</header>

<main>

    <?php if ($mensaje): ?>
        <div class="panel-alert panel-alert-<?= $mensajeTipo ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form class="panel-form" method="POST">
    <?= csrf_field() ?>
        <h3>Agregar periodo académico</h3>
        <div class="field">
            <label for="nombre">Nombre del periodo</label>
            <input type="text" name="nombre" id="nombre" placeholder="Ej. II Bimestre 2026" required>
        </div>
        <button type="submit" class="btn-submit">Guardar periodo</button>
    </form>

    <h2>Periodos registrados (<?= count($periodos) ?>)</h2>

    <?php if (count($periodos) === 0): ?>
        <p class="placeholder-text">Todavía no hay periodos académicos registrados.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Periodo</th></tr></thead>
                <tbody>
                    <?php foreach ($periodos as $p): ?>
                        <tr><td><?= htmlspecialchars($p['nombre']) ?></td></tr>
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
