<?php
// Configuración de CORS
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sesión
session_start();

// Verificar que el usuario está autenticado 
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No has iniciado sesión']);
    exit;
}

// Verificación de administrador según el método isAdmin() de Angular
$isAdmin = false;
if (isset($_SESSION['user']['role'])) {
    $isAdmin = ($_SESSION['user']['role'] === 'admin');
}

// Si no es un administrador, devolver error de permisos
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos para realizar esta acción']);
    exit;
}

require_once("../conexion.php");
$conexion = retornarConexion();

try {
    // Consulta para obtener todos los usuarios
    $stmt = $conexion->prepare("SELECT id, name, email, role, created_at, updated_at FROM users ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode(['result' => 'OK', 'users' => $users]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener usuarios: ' . $e->getMessage()]);
}
?>