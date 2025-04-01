<?php
// Configuración de CORS
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Para este endpoint, no requerimos verificación de admin ya que mostrar categorías
// podría ser necesario para todos los usuarios en la tienda

require("../conexion.php");
$con = retornarConexion();

$query = "SELECT * FROM Categorias";
$result = $con->query($query);

$categorias = array();
while ($fila = $result->fetch_assoc()) {
    $categorias[] = $fila;
}

echo json_encode([
    'result' => 'OK',
    'categorias' => $categorias
]);

$con->close();
?>
