<?php

header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Resto de tu código...
require("../conexion.php");
$con = retornarConexion();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id)) {
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'ID no especificado']);
    exit();
}

$id = intval($data->id);

$sql = "DELETE FROM carrito WHERE id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$response = new stdClass();
$response->resultado = 'OK';
$response->mensaje = 'Producto eliminado del carrito';

$stmt->close();
$con->close();

echo json_encode($response);
?>
