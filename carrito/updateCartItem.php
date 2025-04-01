<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

require("../conexion.php");
$con = retornarConexion();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) || !isset($data->cantidad)) {
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'Datos incompletos']);
    exit();
}

$id = intval($data->id);
$cantidad = intval($data->cantidad);

$sql = "UPDATE carrito SET cantidad = ? WHERE id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("ii", $cantidad, $id);
$stmt->execute();

$response = new stdClass();
$response->resultado = 'OK';
$response->mensaje = 'Cantidad actualizada';

$stmt->close();
$con->close();

echo json_encode($response);
?>