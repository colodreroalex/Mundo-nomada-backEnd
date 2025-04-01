<?php


// Manejo de la petición OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost:4200");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    exit(0);
}

// Cabeceras para la petición
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

// Recoger datos enviados en formato JSON
$json = file_get_contents('php://input');
$params = json_decode($json);

// Validar que se reciban los datos mínimos
if (!isset($params->user_id) || !isset($params->producto_id) || !isset($params->cantidad)) {
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'Datos incompletos']);
    exit();
}

require("../conexion.php");
$con = retornarConexion();

$response = new stdClass();

// Primero, obtenemos el stock actual del producto
$stockStmt = $con->prepare("SELECT stock FROM productos WHERE ProductoID = ?");
$stockStmt->bind_param("i", $params->producto_id);
$stockStmt->execute();
$stockStmt->bind_result($stockDisponible);
if (!$stockStmt->fetch()) {
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'Producto no encontrado']);
    exit();
}
$stockStmt->close();

// Verificamos si el producto ya existe en el carrito para ese usuario
$checkStmt = $con->prepare("SELECT id, cantidad FROM carrito WHERE user_id = ? AND producto_id = ?");
$checkStmt->bind_param("ii", $params->user_id, $params->producto_id);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    // Si ya existe, obtenemos el id y la cantidad actual
    $checkStmt->bind_result($id, $cantidadActual);
    $checkStmt->fetch();
    $nuevaCantidad = $cantidadActual + $params->cantidad;
    $checkStmt->close();

    // Verificar que la cantidad total solicitada no exceda el stock disponible
    if ($nuevaCantidad > $stockDisponible) {
        echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'No hay suficientes unidades disponibles']);
        exit();
    }

    // Actualizamos la cantidad sumando la nueva cantidad
    $updateStmt = $con->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
    $updateStmt->bind_param("ii", $nuevaCantidad, $id);
    if ($updateStmt->execute()) {
        $response->resultado = 'OK';
        $response->mensaje = 'Cantidad actualizada en el carrito';
    } else {
        $response->resultado = 'ERROR';
        $response->mensaje = 'Error al actualizar el carrito';
    }
    $updateStmt->close();
} else {
    $checkStmt->close();
    
    // Si la cantidad a insertar supera el stock disponible, se notifica
    if ($params->cantidad > $stockDisponible) {
        echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'No hay suficientes unidades disponibles']);
        exit();
    }
    
    // Si no existe, insertamos un nuevo registro en el carrito
    $insertStmt = $con->prepare("INSERT INTO carrito (user_id, producto_id, cantidad) VALUES (?, ?, ?)");
    $insertStmt->bind_param("iii", $params->user_id, $params->producto_id, $params->cantidad);
    if ($insertStmt->execute()) {
        $response->resultado = 'OK';
        $response->mensaje = 'Producto añadido al carrito correctamente';
    } else {
        $response->resultado = 'ERROR';
        $response->mensaje = 'Error al añadir el producto al carrito';
    }
    $insertStmt->close();
}

$con->close();
echo json_encode($response);






?>
