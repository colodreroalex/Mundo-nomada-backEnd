<?php
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

// Incluir el archivo de conexión
require("../conexion.php");

// Obtener y decodificar el JSON enviado por el frontend
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verificar que se reciba la información del carrito
if (!isset($data['cart']) || !is_array($data['cart'])) {
    echo json_encode([
        'resultado' => 'ERROR',
        'mensaje'   => 'No se recibió información válida del carrito.'
    ]);
    exit;
}

$cartItems = $data['cart'];
if (count($cartItems) === 0) {
    echo json_encode([
        'resultado' => 'ERROR',
        'mensaje'   => 'El carrito está vacío.'
    ]);
    exit;
}

// Obtener la conexión usando tu función personalizada
$conn = retornarConexion();

// Iniciar transacción para asegurar la atomicidad de las operaciones
$conn->begin_transaction();

try {
    foreach ($cartItems as $item) {
        // Se espera que cada item tenga 'id' (ID del registro en carrito), 'producto_id' y 'cantidad'
        if (!isset($item['id'], $item['producto_id'], $item['cantidad'])) {
            throw new Exception("Datos del carrito incompletos.");
        }

        $carrito_id  = $item['id'];
        $producto_id = $item['producto_id'];
        $cantidad    = $item['cantidad'];

        // Consultar el stock actual del producto
        $stmt = $conn->prepare("SELECT stock FROM productos WHERE ProductoID = ?");
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $conn->error);
        }
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Producto con ID $producto_id no encontrado.");
        }
        $row = $result->fetch_assoc();
        $stock = (int)$row['stock'];
        $stmt->close();

        

        // Verificar que la cantidad reservada siga siendo válida
        if ($stock < $cantidad) {
            throw new Exception("Stock insuficiente para el producto con ID $producto_id. Stock actual: $stock, cantidad reservada: $cantidad.");
        }

        // Reducir el stock del producto
        $nuevo_stock = $stock - $cantidad;
        $stmt = $conn->prepare("UPDATE productos SET stock = ? WHERE ProductoID = ?");
        if (!$stmt) {
            throw new Exception("Error en la preparación de la actualización de stock: " . $conn->error);
        }
        $stmt->bind_param("ii", $nuevo_stock, $producto_id);
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el stock para el producto con ID $producto_id.");
        }
        $stmt->close();

        // Eliminar el item del carrito (o actualizar su estado, según tu lógica)
        $stmt = $conn->prepare("DELETE FROM carrito WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error en la preparación para eliminar el item: " . $conn->error);
        }
        $stmt->bind_param("i", $carrito_id);
        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar el item del carrito con ID $carrito_id.");
        }
        $stmt->close();
    }

    // Si todo salió bien, se confirma la transacción
    $conn->commit();
    echo json_encode([
        'resultado' => 'OK',
        'mensaje'   => 'Compra finalizada exitosamente.'
    ]);
} catch (Exception $e) {
    // En caso de error, se revierte la transacción
    $conn->rollback();
    echo json_encode([
        'resultado' => 'ERROR',
        'mensaje'   => $e->getMessage()
    ]);
}

$conn->close();
?>
