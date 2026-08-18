<?php

session_start();


// ==========================================
// 1. COMPROBAR SESIÓN
// ==========================================

if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../index.html");
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
        id_usuario,
        nombres,
        apellidos,
        correo,
        rol

    FROM usuarios

    WHERE id_usuario = ?
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
        Subdirector - IE 88044
    </title>

    <link
        rel="stylesheet"
        href="../css/dashboard.css"
    >

</head>


<body>


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
        Gestión institucional
    </h2>


    <div class="cards">


        <div class="card">

            <h3>
                📄 Documentos
            </h3>

            <p>
                Revisar y gestionar
                documentos institucionales.
            </p>

            <a href="#">
                Ver documentos
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

            <a href="#">
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

            <a href="#">
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

            <a href="#">
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

            <a href="#">
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

            <a href="#">
                Ver periodos
            </a>

        </div>


    </div>

</main>


</body>

</html>
