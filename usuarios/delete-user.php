<?php
// Configuración de CORS
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
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

// Obtener el ID del usuario a eliminar
$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['id']) ? $input['id'] : (isset($_GET['id']) ? $_GET['id'] : null);

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Se requiere el ID del usuario']);
    exit;
}

// Verificar que no se esté intentando eliminar al propio usuario administrador actual
if ($userId == $_SESSION['user']['id']) {
    http_response_code(400);
    echo json_encode(['error' => 'No puedes eliminar tu propia cuenta de administrador']);
    exit;
}

// Verificar si el usuario existe
$stmt = $conexion->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}

// Opcional: Si necesitas implementar restricciones adicionales para proteger a ciertos usuarios
// Por ejemplo, no permitir eliminar al último administrador o a usuarios específicos
$userData = $result->fetch_assoc();
if ($userData['role'] === 'admin') {
    // Contar cuántos administradores hay en total
    $adminCountStmt = $conexion->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $adminCount = $adminCountStmt->fetch_assoc()['count'];
    
    if ($adminCount <= 1) {
        http_response_code(400);
        echo json_encode(['error' => 'No se puede eliminar al único administrador del sistema']);
        exit;
    }
}

try {
    // Iniciar transacción para asegurar la integridad de los datos
    $conexion->begin_transaction();
    
    // Opcionalmente, si hay tablas relacionadas con usuarios (como pedidos, etc.)
    // deberías actualizar esas tablas primero o implementar una política de eliminación
    
    // Eliminar el usuario
    $stmt = $conexion->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        // Confirmar la transacción
        $conexion->commit();
        
        echo json_encode([
            'result' => 'OK',
            'message' => 'Usuario eliminado exitosamente'
        ]);
    } else {
        // Rollback si no se eliminó ningún registro
        $conexion->rollback();
        
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar el usuario']);
    }
} catch (Exception $e) {
    // Rollback en caso de error
    $conexion->rollback();
    
    http_response_code(500);
    echo json_encode(['error' => 'Error al eliminar el usuario: ' . $e->getMessage()]);
}
?>