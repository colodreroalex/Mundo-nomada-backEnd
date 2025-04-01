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
require_once("../hash.php");
$conexion = retornarConexion();

// Obtener y decodificar los datos JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

// Validar campos requeridos
if (empty($input['name']) || empty($input['email']) || empty($input['password']) || empty($input['role'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan campos requeridos']);
    exit;
}

$name = $input['name'];
$email = $input['email'];
$password = $input['password'];
$role = $input['role'];

// Validar que el role solo pueda ser 'user' o 'admin'
if ($role !== 'user' && $role !== 'admin') {
    http_response_code(400);
    echo json_encode(['error' => 'Rol no válido']);
    exit;
}

try {
    // Verificar si el email ya existe
    $stmt = $conexion->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'El email ya está registrado']);
        exit;
    }
    
    // Hash de la contraseña
    $hashedPassword = hash_password($password);
    
    // Insertar el nuevo usuario
    $stmt = $conexion->prepare("INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $userId = $conexion->insert_id;
        echo json_encode([
            'result' => 'OK',
            'message' => 'Usuario creado exitosamente',
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => $role
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear el usuario']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear el usuario: ' . $e->getMessage()]);
}
?>