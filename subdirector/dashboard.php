<?php

session_start();


// ==========================================
// 1. COMPROBAR SESIÓN
// ==========================================

if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.html");
    exit;

}


// ==========================================
// 2. COMPROBAR ROL
// ==========================================

if ($_SESSION["rol"] !== "SUBDIRECTOR") {

    die("Acceso no autorizado.");

}


// ==========================================
// 3. CONECTAR A MYSQL
// ==========================================

require_once "../backend/config/database.php";


// ==========================================
// 4. OBTENER DATOS DEL SUBDIRECTOR
// ==========================================

$sql = "
    SELECT
        u.id_usuario,
        u.nombres,
        u.apellidos,
        u.correo,
        r.nombre AS rol

    FROM usuarios u

    INNER JOIN roles r
        ON u.id_rol = r.id_rol

    WHERE u.id_usuario = ?
";

$stmt = $conexion->prepare($sql);

$stmt->execute([
    $_SESSION["id_usuario"]
]);

$usuario = $stmt->fetch();


// ==========================================
// 5. COMPROBAR USUARIO
// ==========================================

if (!$usuario) {

    die("No se encontró el usuario.");

}


// ==========================================
// 6. RESUMEN DE TRABAJOS ENVIADOS POR PROFESORES
// (datos reales de envios_trabajo; no se inventan cifras)
// ==========================================

$resumenEnvios = $conexion->query("
    SELECT
        SUM(estado IN ('ENVIADO', 'EN_REVISION', 'CORREGIDO')) AS pendientes,
        SUM(estado = 'REQUIERE_CAMBIOS') AS requieren_cambios,
        SUM(estado = 'APROBADO') AS aprobados,
        COUNT(*) AS total
    FROM envios_trabajo
")->fetch();

$ultimosPendientes = $conexion->query("
    SELECT
        e.id_envio, e.titulo, e.estado,
        u.nombres, u.apellidos,
        h.creado_en AS fecha_version
    FROM envios_trabajo e
    INNER JOIN profesores p ON p.id_profesor = e.id_profesor
    INNER JOIN usuarios u ON u.id_usuario = p.id_usuario
    INNER JOIN envios_trabajo_historial h
        ON h.id_envio = e.id_envio AND h.version = e.version_actual
    WHERE e.estado IN ('ENVIADO', 'EN_REVISION', 'CORREGIDO')
    ORDER BY h.creado_en DESC
    LIMIT 5
")->fetchAll();

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
        Subdirector - I.E.P. 88044 Abraham Valdelomar
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

        <h1>
            Panel del Subdirector
        </h1>

        <p>

            Bienvenido,

            <?= htmlspecialchars($usuario["nombres"]) ?>

            <?= htmlspecialchars($usuario["apellidos"]) ?>

        </p>

    </div>


    <a href="../backend/auth/logout.php">
        Cerrar sesión
    </a>

</header>



<main>


    <section class="profile-card">

        <h2>
            Mi información
        </h2>


        <p>

            <strong>
                Nombre:
            </strong>

            <?= htmlspecialchars($usuario["nombres"]) ?>

            <?= htmlspecialchars($usuario["apellidos"]) ?>

        </p>


        <p>

            <strong>
                Correo:
            </strong>

            <?= htmlspecialchars($usuario["correo"]) ?>

        </p>


        <p>

            <strong>
                Rol:
            </strong>

            <?= htmlspecialchars($usuario["rol"]) ?>

        </p>

    </section>


    <h2>
        Supervisión académica
    </h2>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-value"><?= (int) ($resumenEnvios["pendientes"] ?? 0) ?></span>
            <span class="stat-label">Pendientes de revisión</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= (int) ($resumenEnvios["requieren_cambios"] ?? 0) ?></span>
            <span class="stat-label">Requieren cambios</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= (int) ($resumenEnvios["aprobados"] ?? 0) ?></span>
            <span class="stat-label">Aprobados</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= (int) ($resumenEnvios["total"] ?? 0) ?></span>
            <span class="stat-label">Total de envíos</span>
        </div>
    </div>

    <section>
        <h3>Pendientes de revisión</h3>

        <?php if (count($ultimosPendientes) === 0): ?>
            <p class="placeholder-text">No hay trabajos pendientes de revisión.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Profesor</th><th>Trabajo</th><th>Fecha</th><th>Estado</th><th>Acción</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosPendientes as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p["nombres"] . " " . $p["apellidos"]) ?></td>
                                <td><?= htmlspecialchars($p["titulo"]) ?></td>
                                <td><?= htmlspecialchars($p["fecha_version"]) ?></td>
                                <td><span class="status-badge status-<?= strtolower($p["estado"]) ?>"><?= htmlspecialchars($p["estado"]) ?></span></td>
                                <td><a href="revision.php" class="btn-mini">Revisar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p><a href="revision.php">Ver todos los trabajos enviados &rarr;</a></p>
        <?php endif; ?>
    </section>


    <h2>
        Gestión institucional
    </h2>


    <div class="cards">


        <div class="card">

            <h3>
                🎓 Alumnos
            </h3>

            <p>
                Consultar alumnos
                matriculados.
            </p>

            <a href="alumnos.php">
                Ver alumnos
            </a>

        </div>



        <div class="card">

            <h3>
                📄 Documentos
            </h3>

            <p>
                Revisar y gestionar
                documentos institucionales.
            </p>

            <a href="documentos.php">
                Ver documentos
            </a>

        </div>



        <div class="card">

            <h3>
                📝 Revisión de trabajos
            </h3>

            <p>
                Revisar los trabajos enviados
                por los profesores, aprobar o
                solicitar correcciones.
            </p>

            <a href="revision.php">
                Ir a revisión
            </a>

        </div>



        <div class="card">

            <h3>
                📋 Solicitudes
            </h3>

            <p>
                Revisar y gestionar
                solicitudes de los usuarios.
            </p>

            <a href="solicitudes.php">
                Ver solicitudes
            </a>

        </div>



        <div class="card">

            <h3>
                👨‍🏫 Profesores
            </h3>

            <p>
                Consultar información
                del personal docente.
            </p>

            <a href="profesores.php">
                Ver profesores
            </a>

        </div>



        <div class="card">

            <h3>
                📊 Reportes
            </h3>

            <p>
                Consultar reportes
                institucionales.
            </p>

            <a href="reportes.php">
                Ver reportes
            </a>

        </div>



        <div class="card">

            <h3>
                📢 Comunicados
            </h3>

            <p>
                Gestionar comunicados
                institucionales.
            </p>

            <a href="comunicados.php">
                Ver comunicados
            </a>

        </div>



        <div class="card">

            <h3>
                📅 Periodos académicos
            </h3>

            <p>
                Consultar los periodos
                académicos registrados.
            </p>

            <a href="periodos.php">
                Ver periodos
            </a>

        </div>


    </div>

</main>


</div><!-- /.app-content -->
</div><!-- /.app-shell -->

<script src="../js/panel.js"></script>

</body>

</html>
