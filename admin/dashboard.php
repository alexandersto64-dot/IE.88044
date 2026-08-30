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

if ($_SESSION["rol"] !== "ADMIN") {

    die("Acceso no autorizado.");

}


// ==========================================
// 3. CONECTAR A MYSQL
// ==========================================

require_once "../backend/config/database.php";


// ==========================================
// 4. OBTENER DATOS DEL ADMINISTRADOR
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
// 6. ESTADÍSTICAS REALES DEL SISTEMA
// (todas las cifras salen de consultas directas; si una tabla
// no existiera, la consulta fallaría en vez de mostrar un dato
// inventado)
// ==========================================

$totalUsuarios = (int) $conexion->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalProfesores = (int) $conexion->query("SELECT COUNT(*) FROM profesores")->fetchColumn();
$totalAlumnos = (int) $conexion->query("SELECT COUNT(*) FROM alumnos")->fetchColumn();
$totalCursos = (int) $conexion->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
$totalMatriculas = (int) $conexion->query("SELECT COUNT(*) FROM matriculas")->fetchColumn();
$totalDocumentos = (int) $conexion->query("SELECT COUNT(*) FROM documentos")->fetchColumn();

$resumenTrabajos = $conexion->query("
    SELECT
        SUM(estado IN ('ENVIADO', 'EN_REVISION', 'CORREGIDO')) AS pendientes,
        SUM(estado = 'APROBADO') AS aprobados,
        SUM(estado = 'REQUIERE_CAMBIOS') AS requieren_cambios
    FROM envios_trabajo
")->fetch();

$ultimosTrabajos = $conexion->query("
    SELECT e.titulo, e.estado, h.creado_en, u.nombres, u.apellidos
    FROM envios_trabajo e
    INNER JOIN profesores p ON p.id_profesor = e.id_profesor
    INNER JOIN usuarios u ON u.id_usuario = p.id_usuario
    INNER JOIN envios_trabajo_historial h ON h.id_envio = e.id_envio AND h.version = e.version_actual
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
        Administrador - I.E.P. 88044 Abraham Valdelomar
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
            Panel de Administración
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
            Mi cuenta
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
        Panel de Administración Institucional
    </h2>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-value"><?= $totalUsuarios ?></span>
            <span class="stat-label">Usuarios</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalProfesores ?></span>
            <span class="stat-label">Profesores</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalAlumnos ?></span>
            <span class="stat-label">Alumnos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalCursos ?></span>
            <span class="stat-label">Cursos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalMatriculas ?></span>
            <span class="stat-label">Matrículas</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalDocumentos ?></span>
            <span class="stat-label">Documentos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= (int) ($resumenTrabajos["pendientes"] ?? 0) ?></span>
            <span class="stat-label">Trabajos pendientes</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= (int) ($resumenTrabajos["aprobados"] ?? 0) ?></span>
            <span class="stat-label">Trabajos aprobados</span>
        </div>
    </div>

    <section>
        <h3>Últimos trabajos enviados</h3>

        <?php if (count($ultimosTrabajos) === 0): ?>
            <p class="placeholder-text">Todavía no hay trabajos enviados en el sistema.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Profesor</th><th>Trabajo</th><th>Fecha</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosTrabajos as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t["nombres"] . " " . $t["apellidos"]) ?></td>
                                <td><?= htmlspecialchars($t["titulo"]) ?></td>
                                <td><?= htmlspecialchars($t["creado_en"]) ?></td>
                                <td><span class="status-badge status-<?= strtolower($t["estado"]) ?>"><?= htmlspecialchars($t["estado"]) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>


    <h2>
        Administración
    </h2>


    <div class="cards">


        <div class="card">

            <h3>
                👥 Usuarios
            </h3>

            <p>
                Crear, editar y administrar
                usuarios del sistema.
            </p>

            <a href="usuarios.php">
                Gestionar usuarios
            </a>

        </div>



        <div class="card">

            <h3>
                🎓 Alumnos
            </h3>

            <p>
                Registrar alumnos y
                gestionar sus matrículas.
            </p>

            <a href="alumnos.php">
                Gestionar alumnos
            </a>

        </div>



        <div class="card">

            <h3>
                👨‍🏫 Profesores
            </h3>

            <p>
                Administrar la información
                de los profesores.
            </p>

            <a href="profesores.php">
                Gestionar profesores
            </a>

        </div>



        <div class="card">

            <h3>
                📄 Documentos
            </h3>

            <p>
                Administrar los documentos
                institucionales.
            </p>

            <a href="documentos.php">
                Ver documentos
            </a>

        </div>



        <div class="card">

            <h3>
                📊 Reportes
            </h3>

            <p>
                Consultar reportes
                del sistema.
            </p>

            <a href="reportes.php">
                Ver reportes
            </a>

        </div>



        <div class="card">

            <h3>
                📚 Cursos
            </h3>

            <p>
                Administrar cursos
                académicos.
            </p>

            <a href="cursos.php">
                Gestionar cursos
            </a>

        </div>



        <div class="card">

            <h3>
                ⚙️ Configuración
            </h3>

            <p>
                Configurar opciones
                del sistema.
            </p>

            <a href="configuracion.php">
                Configuración
            </a>

        </div>


    </div>

</main>


</div><!-- /.app-content -->
</div><!-- /.app-shell -->

<script src="../js/panel.js"></script>

</body>

</html>
