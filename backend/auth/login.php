<?php

session_start();

require_once "../config/database.php";


// ==========================================
// COMPROBAR MÉTODO POST
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../../index.html");
    exit;

}


// ==========================================
// OBTENER DATOS DEL FORMULARIO
// ==========================================

$correo = trim($_POST["correo"] ?? "");
$password = $_POST["password"] ?? "";


if ($correo === "" || $password === "") {

    die("Debe completar todos los campos.");

}


// ==========================================
// BUSCAR USUARIO
// ==========================================

$sql = "
    SELECT
        u.id_usuario,
        u.nombres,
        u.apellidos,
        u.correo,
        u.password,
        u.estado,
        r.id_rol,
        r.nombre AS rol

    FROM usuarios u

    INNER JOIN roles r
        ON u.id_rol = r.id_rol

    WHERE u.correo = ?

    LIMIT 1
";


$stmt = $conexion->prepare($sql);

$stmt->execute([$correo]);

$usuario = $stmt->fetch();


// ==========================================
// COMPROBAR USUARIO
// ==========================================

if (!$usuario) {

    die("Correo o contraseña incorrectos.");

}


// ==========================================
// COMPROBAR ESTADO
// ==========================================

if ($usuario["estado"] !== "ACTIVO") {

    die("El usuario se encuentra inactivo.");

}


// ==========================================
// COMPROBAR CONTRASEÑA
// ==========================================

if (!password_verify($password, $usuario["password"])) {

    die("Correo o contraseña incorrectos.");

}


// ==========================================
// CREAR SESIÓN
// ==========================================

session_regenerate_id(true);

$_SESSION["id_usuario"] = $usuario["id_usuario"];

$_SESSION["nombres"] = $usuario["nombres"];

$_SESSION["apellidos"] = $usuario["apellidos"];

$_SESSION["correo"] = $usuario["correo"];

$_SESSION["rol"] = $usuario["rol"];

$_SESSION["id_rol"] = $usuario["id_rol"];


// ==========================================
// ACTUALIZAR ÚLTIMO ACCESO
// ==========================================

$sql = "
    UPDATE usuarios

    SET ultimo_acceso = NOW()

    WHERE id_usuario = ?
";


$stmt = $conexion->prepare($sql);

$stmt->execute([
    $usuario["id_usuario"]
]);


// ==========================================
// REDIRECCIONAR SEGÚN ROL
// ==========================================

switch (strtoupper(trim($usuario["rol"]))) {

    case "ADMIN":

        header(
            "Location: ../../admin/dashboard.php"
        );

        exit;


    case "SUBDIRECTOR":

        header(
            "Location: ../../subdirector/dashboard.php"
        );

        exit;


    case "PROFESOR":

        header(
            "Location: ../../profesor/dashboard.php"
        );

        exit;


    default:

        session_destroy();

        die("Rol de usuario no válido.");

}
