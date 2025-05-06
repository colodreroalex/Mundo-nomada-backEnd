<?php
// Configuración de CORS
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar o reanudar la sesión
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'No has iniciado sesión']);
    exit;
}

require_once("../conexion.php");
$conexion = retornarConexion();

$user_id = $_SESSION['user']['id'];

try {
    $stmt = $conexion->prepare("SELECT id, fecha, total, estado FROM orders WHERE user_id = ? ORDER BY fecha DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    echo json_encode(['resultado' => 'OK', 'orders' => $orders]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener pedidos: ' . $e->getMessage()]);
}
?>