<?php
// Configuración de CORS
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No has iniciado sesión']);
    exit;
}

require_once("../conexion.php");
$conexion = retornarConexion();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['total']) || !isset($data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos de pedido incompletos']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$total = $data['total'];
$estado = 'pendiente';
$fecha = date('Y-m-d H:i:s');

try {
    // Iniciar transacción
    $conexion->begin_transaction();
    // Insertar pedido
    $stmt = $conexion->prepare("INSERT INTO orders (user_id, fecha, total, estado) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $user_id, $fecha, $total, $estado);
    $stmt->execute();
    $order_id = $conexion->insert_id;
    // Insertar productos del pedido
    $stmtItem = $conexion->prepare("INSERT INTO order_items (order_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    foreach ($data['items'] as $item) {
        $stmtItem->bind_param("iiid", $order_id, $item['producto_id'], $item['cantidad'], $item['precio_unitario']);
        $stmtItem->execute();
    }
    $conexion->commit();
    echo json_encode(['result' => 'OK', 'order_id' => $order_id]);
} catch (Exception $e) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear el pedido: ' . $e->getMessage()]);
}
?>