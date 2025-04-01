<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
// Añadir headers para prevenir caché
require("../conexion.php");
$con = retornarConexion();

// Obtener los datos enviados en formato JSON
$data = json_decode(file_get_contents("php://input"), true);

// Validar que se hayan enviado los IDs de productos
if (!isset($data['ids']) || !is_array($data['ids']) || count($data['ids']) === 0) {
    echo json_encode([
        'resultado' => 'ERROR',
        'mensaje'   => 'IDs de productos no especificados o vacíos'
    ]);
    exit();
}

// Convertir los IDs a enteros por seguridad
$ids = array_map('intval', $data['ids']);

// Crear los placeholders según la cantidad de IDs
$placeholders = implode(',', array_fill(0, count($ids), '?'));

// Preparar la consulta SQL, filtrando para que solo se devuelvan productos con stock > 0
$sql = "SELECT ProductoID, nombre, precio, descripcion, stock, categoriaID, imagen 
        FROM productos 
        WHERE ProductoID IN ($placeholders) AND stock > 0";
$stmt = $con->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'resultado' => 'ERROR',
        'mensaje'   => 'Error en la preparación de la consulta: ' . $con->error
    ]);
    exit();
}

// Generar la cadena de tipos (un 'i' por cada ID)
$types = str_repeat('i', count($ids));
// Usamos el operador spread para pasar los IDs al bind_param
$stmt->bind_param($types, ...$ids);

$stmt->execute();
$result = $stmt->get_result();

$productos = array();
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

// Construir la respuesta JSON con los productos actualizados
$response = new stdClass();
$response->resultado = 'OK';
$response->products = $productos;

$stmt->close();
$con->close();

echo json_encode($response);
?>
