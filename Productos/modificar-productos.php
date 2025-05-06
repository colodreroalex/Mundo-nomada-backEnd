<?php
// Configuración de CORS
header('Access-Control-Allow-Origin: http://localhost:4200'); 
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Credentials: true');
header("Access-Control-Allow-Methods: PUT, POST, OPTIONS");
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

// Activa el reporte de errores para depuración (en producción puedes desactivarlo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Recibe y decodifica el JSON de entrada
$json = file_get_contents('php://input');
$params = json_decode($json);

// Valida que tenemos todos los campos necesarios
if (!isset($params->id) || !isset($params->nombre) || !isset($params->precio) || !isset($params->categoriaID)) {
    http_response_code(400);
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'Faltan campos requeridos']);
    exit;
}

// Validar que el precio sea mayor a 0
if (floatval($params->precio) <= 0) {
    http_response_code(400);
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'El precio debe ser mayor a 0.']);
    exit;
}

// Incluye la conexión
require("../conexion.php");
$con = retornarConexion();

// Iniciamos una transacción
$con->begin_transaction();

try {
    // Verificamos primero si el producto existe
    $checkStmt = $con->prepare("SELECT COUNT(*) as count FROM Productos WHERE ProductoID = ?");
    $checkStmt->bind_param("i", $params->id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $checkStmt->close();

    if ($row['count'] == 0) {
        throw new Exception("El producto no existe");
    }

    // Verificamos si la categoría existe
    $catStmt = $con->prepare("SELECT COUNT(*) as count FROM Categorias WHERE CategoriaID = ?");
    $catStmt->bind_param("i", $params->categoriaID);
    $catStmt->execute();
    $result = $catStmt->get_result();
    $row = $result->fetch_assoc();
    $catStmt->close();

    if ($row['count'] == 0) {
        throw new Exception("La categoría seleccionada no existe");
    }

    // Actualizamos el producto
    $stmt = $con->prepare("UPDATE Productos SET 
                           nombre = ?, 
                           precio = ?, 
                           descripcion = ?, 
                           stock = ?, 
                           categoriaID = ?, 
                           imagen = ?,
                           color = ?,
                           talla = ? 
                           WHERE ProductoID = ?");
                           
    $stmt->bind_param("sdssisssi", 
        $params->nombre, 
        $params->precio, 
        $params->descripcion, 
        $params->stock, 
        $params->categoriaID, 
        $params->imagen,
        $params->color,
        $params->talla,
        $params->id
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar el producto: " . $stmt->error);
    }
    
    if ($stmt->affected_rows == 0) {
        // No hubo cambios reales en los datos, pero no es un error
        // Podría ser que se enviaron los mismos valores
        $con->commit();
        echo json_encode(['resultado' => 'OK', 'mensaje' => 'No se detectaron cambios en el producto']);
        exit;
    }
    
    $stmt->close();
    
    // Todo salió bien, confirmamos la transacción
    $con->commit();
    
    echo json_encode(['resultado' => 'OK', 'mensaje' => 'Producto actualizado correctamente']);
    
} catch (Exception $e) {
    // Algo salió mal, revertimos la transacción
    $con->rollback();
    
    http_response_code(400);
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => $e->getMessage()]);
} finally {
    $con->close();
}
?>
