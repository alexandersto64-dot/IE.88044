<?php

session_start();


// ==========================================
// CERRAR TODAS LAS VARIABLES DE SESIÓN
// ==========================================

$_SESSION = [];


// ==========================================
// ELIMINAR LA COOKIE DE SESIÓN
// ==========================================

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


// ==========================================
// DESTRUIR LA SESIÓN
// ==========================================

session_destroy();


// ==========================================
// VOLVER AL LOGIN
// ==========================================

header("Location: ../../index.html");

exit;
