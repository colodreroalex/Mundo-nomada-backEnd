<?php
// Manejo de CORS y cabeceras
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

require("../conexion.php");
$con = retornarConexion();

// Verificar que se haya enviado el parámetro user_id
if (!isset($_GET['user_id'])) {
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'user_id no especificado']);
    exit();
}

$user_id = intval($_GET['user_id']);

// Consulta para obtener el precio total del carrito: se multiplica la cantidad por el precio de cada producto y se suma el total
$sql = "SELECT SUM(c.cantidad * p.precio) AS total 
        FROM carrito c 
        INNER JOIN productos p ON c.producto_id = p.ProductoID 
        WHERE c.user_id = ?";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
if ($row = $result->fetch_assoc()) {
    // En caso de que el resultado sea NULL (carrito vacío), se asigna 0
    $total = $row['total'] ? $row['total'] : 0;
}

$response = new stdClass();
$response->resultado = 'OK';
$response->total = $total;

$stmt->close();
$con->close();

echo json_encode($response);
?>
