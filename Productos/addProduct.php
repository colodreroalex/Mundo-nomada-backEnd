<?php
// Configuración de CORS
header('Access-Control-Allow-Origin: http://localhost:4200');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Credentials: true');
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json');

// Manejo de la petición OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sesión
session_start();

// Verificar que el usuario está autenticado 
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No has iniciado sesión']);
    exit;
}

// Verificación de administrador según el método isAdmin() de Angular
$isAdmin = false;
if (isset($_SESSION['user']['role'])) {
    $isAdmin = ($_SESSION['user']['role'] === 'admin');
}

// Si no es un administrador, devolver error de permisos
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos para realizar esta acción']);
    exit;
}

$json = file_get_contents('php://input');
$params = json_decode($json);

require("../conexion.php");
$con = retornarConexion();

// Validar que todos los campos requeridos están presentes
if (!isset($params->nombre) || !isset($params->precio) || !isset($params->categoriaID) || !isset($params->descripcion)) {
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'Faltan campos requeridos']);
    exit;
}

// Extraer los valores
$nombre = $params->nombre;
$precio = $params->precio;
$categoriaID = $params->categoriaID;
$descripcion = $params->descripcion;
$stock = isset($params->stock) ? $params->stock : 0;
$imagen = isset($params->imagen) ? $params->imagen : '';
$color = isset($params->color) ? $params->color : '';
$talla = isset($params->talla) ? $params->talla : '';

// Iniciar transacción
$con->begin_transaction();

try {
    // Verificar si la categoría existe
    $stmtCategoria = $con->prepare("SELECT COUNT(*) as total FROM Categorias WHERE CategoriaID = ?");
    $stmtCategoria->bind_param("i", $categoriaID);
    $stmtCategoria->execute();
    $resultCategoria = $stmtCategoria->get_result();
    $rowCategoria = $resultCategoria->fetch_assoc();
    
    if ($rowCategoria['total'] == 0) {
        throw new Exception("La categoría seleccionada no existe");
    }

    // Verificar si ya existe un producto con el mismo nombre, color y talla
    $checkStmt = $con->prepare("SELECT COUNT(*) as count FROM Productos WHERE nombre = ? AND color = ? AND talla = ?");
    $checkStmt->bind_param("sss", $nombre, $color, $talla);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $checkStmt->close();

    if ($row['count'] > 0) {
        throw new Exception("Ya existe un producto con el mismo nombre, color y talla");
    }

    // Insertar el producto
    $stmt = $con->prepare("INSERT INTO Productos (nombre, precio, categoriaID, descripcion, stock, imagen, color, talla) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdisssss", $nombre, $precio, $categoriaID, $descripcion, $stock, $imagen, $color, $talla);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al registrar el producto: " . $stmt->error);
    }
    
    // Confirmar transacción
    $con->commit();
    
    echo json_encode(['resultado' => 'OK', 'mensaje' => 'Producto registrado correctamente', 'id' => $con->insert_id]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $con->rollback();
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => $e->getMessage()]);
} finally {
    $con->close();
}
?>
