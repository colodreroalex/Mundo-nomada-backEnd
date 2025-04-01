<?php
// Configuración de CORS y manejo de preflight
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require("../conexion.php");
$conexion = retornarConexion();

// Recibir y decodificar la entrada JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos']);
    exit;
}

$email = $input['email'];
$password = trim($input['password']);

// Usar sentencias preparadas para evitar inyección SQL
$stmt = $conexion->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        // Iniciar la sesión y regenerar el ID
        session_start(); //lo estableci en el php.ini a 1h
        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
        
        // Implementar cookie "Remember Me" si se solicita
        // En el bloque "Remember Me":
        if (isset($input['rememberMe']) && $input['rememberMe'] === true) {
            $token = bin2hex(random_bytes(16));
            $stmtToken = $conexion->prepare("UPDATE users SET remember_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?");
    
            if (!$stmtToken) {
                // Manejar error de preparación
                error_log("Error en prepare: " . $conexion->error);
                http_response_code(500);
                echo json_encode(['error' => 'Error interno']);
                exit;
            }
    
            $stmtToken->bind_param("si", $token, $user['id']);
            if (!$stmtToken->execute()) {
                // Manejar error de ejecución
                error_log("Error en execute: " . $stmtToken->error);
            }
    
            // Configuración moderna de la cookie
            setcookie('remember_me', $token, [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        echo json_encode($user);
        exit;
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Contraseña incorrecta']);
        exit;
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}
?>





