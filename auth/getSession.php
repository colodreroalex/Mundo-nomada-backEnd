<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

session_start();

if (isset($_SESSION['user'])) {
    // Si ya hay una sesión activa, se devuelve el usuario
    echo json_encode($_SESSION['user']);
    exit;
}

// Si no hay sesión activa, se intenta validar la cookie "remember_me"
if (isset($_COOKIE['remember_me'])) {
    require("../conexion.php");
    $conexion = retornarConexion();
    $token = $_COOKIE['remember_me'];
    
    $stmt = $conexion->prepare("SELECT * FROM users WHERE remember_token = ? AND token_expiry > NOW() LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // No es necesario llamar nuevamente a session_start()
        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
        echo json_encode($user);
        exit;
    }
}

// Si no se encontró cookie válida o no existe, se retorna error
http_response_code(401);
echo json_encode(['error' => 'No hay sesión activa']);


?>
