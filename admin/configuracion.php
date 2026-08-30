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
require_once "../backend/config/seguridad_csrf.php";
$mensaje = null;
$mensajeTipo = null;

$camposPermitidos = ["nombre_colegio", "correo_contacto", "telefono_contacto"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_verificar();

    $stmt = $conexion->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");

    foreach ($camposPermitidos as $clave) {
        $valor = trim($_POST[$clave] ?? "");
        $stmt->execute([$valor, $clave]);
    }

    $mensaje = "Configuración guardada correctamente.";
    $mensajeTipo = "success";

}

$filas = $conexion->query("SELECT clave, valor FROM configuracion")->fetchAll(PDO::FETCH_KEY_PAIR);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración · Panel de Administración - I.E.P. 88044 Abraham Valdelomar</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<div class="app-shell">
<?php $currentFile = basename(__FILE__); include __DIR__ . "/../backend/partials/sidebar.php"; ?>
<div class="app-content">


<header>
    <div>
        <h1>Configuración</h1>
        <p>Panel de Administración · I.E.P. 88044 Abraham Valdelomar</p>
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
        <h3>Datos institucionales</h3>

        <div class="field">
            <label for="nombre_colegio">Nombre del colegio</label>
            <input type="text" name="nombre_colegio" id="nombre_colegio"
                   value="<?= htmlspecialchars($filas['nombre_colegio'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="field">
                <label for="correo_contacto">Correo de contacto</label>
                <input type="email" name="correo_contacto" id="correo_contacto"
                       value="<?= htmlspecialchars($filas['correo_contacto'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="telefono_contacto">Teléfono de contacto</label>
                <input type="text" name="telefono_contacto" id="telefono_contacto"
                       value="<?= htmlspecialchars($filas['telefono_contacto'] ?? '') ?>">
            </div>
        </div>

        <button type="submit" class="btn-submit">Guardar cambios</button>
    </form>

</main>

</div><!-- /.app-content -->
</div><!-- /.app-shell -->

<script src="../js/panel.js"></script>

</body>
</html>
