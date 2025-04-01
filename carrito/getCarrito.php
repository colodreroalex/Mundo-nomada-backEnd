<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

require("../conexion.php");
$con = retornarConexion();

// Se espera recibir el user_id por GET
if (!isset($_GET['user_id'])) {
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'user_id no especificado']);
    exit();
}

$user_id = intval($_GET['user_id']);

// Consulta para obtener los productos del carrito junto con los detalles del producto
$sql = "SELECT 
    c.id AS cart_id,
    c.cantidad,
    p.ProductoID,
    p.nombre,
    p.precio,
    p.descripcion,
    p.stock,
    p.categoriaID,
    p.imagen
FROM carrito c
INNER JOIN productos p ON c.producto_id = p.ProductoID
WHERE c.user_id = ?
";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = array();
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$response = new stdClass();
$response->resultado = 'OK';
$response->datos = $items;

$stmt->close();
$con->close();

echo json_encode($response);
?>
