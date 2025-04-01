<?php

header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

session_start();
$_SESSION = array();

// Eliminar la cookie de sesión de forma segura
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Eliminar la cookie "remember_me" si existe
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, "/", "", isset($_SERVER["HTTPS"]), true);
}

echo json_encode(['message' => 'Sesión cerrada']);



 ?>
