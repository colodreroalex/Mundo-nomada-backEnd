<?php
// Configuración de CORS
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
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

// Validar que existe un ID de usuario
if (empty($input['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Se requiere el ID del usuario']);
    exit;
}

$userId = $input['id'];
$updates = [];
$types = '';
$params = [];

// Verificar si el usuario existe
$stmt = $conexion->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}

$currentUser = $result->fetch_assoc();

// Preparar las actualizaciones
if (!empty($input['name'])) {
    $updates[] = "name = ?";
    $types .= "s";
    $params[] = $input['name'];
}

if (!empty($input['email'])) {
    // Verificar si el email ya existe y no pertenece a este usuario
    if ($input['email'] !== $currentUser['email']) {
        $stmt = $conexion->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $input['email'], $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'El email ya está registrado por otro usuario']);
            exit;
        }
    }
    
    $updates[] = "email = ?";
    $types .= "s";
    $params[] = $input['email'];
}

if (!empty($input['password'])) {
    $updates[] = "password = ?";
    $types .= "s";
    $params[] = hash_password($input['password']);
}

if (!empty($input['role'])) {
    // Validar que el role solo pueda ser 'user' o 'admin'
    if ($input['role'] !== 'user' && $input['role'] !== 'admin') {
        http_response_code(400);
        echo json_encode(['error' => 'Rol no válido']);
        exit;
    }
    
    $updates[] = "role = ?";
    $types .= "s";
    $params[] = $input['role'];
}

// Si no hay nada que actualizar
if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['error' => 'No se proporcionaron campos para actualizar']);
    exit;
}

// Añadir la fecha de actualización
$updates[] = "updated_at = NOW()";

// Añadir el ID al final de los parámetros
$types .= "i";
$params[] = $userId;

// Construir y ejecutar la consulta de actualización
$query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();

if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
    // Consultar los datos actualizados para devolver
    $stmt = $conexion->prepare("SELECT id, name, email, role, created_at, updated_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $updatedUser = $result->fetch_assoc();
    
    echo json_encode([
        'result' => 'OK',
        'message' => 'Usuario actualizado exitosamente',
        'user' => $updatedUser
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar el usuario: ' . $conexion->error]);
}
?>