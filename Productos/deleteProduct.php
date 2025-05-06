<?php
// Configuración de CORS
header('Access-Control-Allow-Origin: http://localhost:4200');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Credentials: true');
header("Access-Control-Allow-Methods: DELETE, POST, GET, OPTIONS");
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
  echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'No has iniciado sesión']);
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
  echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'No tienes permisos para realizar esta acción']);
  exit;
}

// Obtener ID del producto
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || $_SERVER['REQUEST_METHOD'] === 'GET') {
  $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
} else {
  $json = file_get_contents('php://input');
  $params = json_decode($json);
  $id = isset($params->id) ? intval($params->id) : 0;
}

if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'ID de producto no especificado o inválido']);
  exit;
}

// Incluimos el archivo de conexión
require_once("../conexion.php");
$con = retornarConexion();

// Iniciar transacción
$con->begin_transaction();

try {
  // Primero verificamos si el producto existe
  $checkStmt = $con->prepare("SELECT COUNT(*) as count FROM Productos WHERE ProductoID = ?");
  $checkStmt->bind_param("i", $id);
  $checkStmt->execute();
  $result = $checkStmt->get_result();
  $row = $result->fetch_assoc();
  $checkStmt->close();

  if ($row['count'] == 0) {
    throw new Exception("El producto no existe");
  }

  // Eliminamos el producto
  $stmt = $con->prepare("DELETE FROM Productos WHERE ProductoID = ?");
  $stmt->bind_param("i", $id);
  
  if (!$stmt->execute()) {
    throw new Exception("Error al eliminar el producto: " . $stmt->error);
  }
  
  if ($stmt->affected_rows == 0) {
    throw new Exception("No se pudo eliminar el producto");
  }

  $stmt->close();
  
  // Confirmamos la transacción
  $con->commit();
  
  http_response_code(200);
  echo json_encode(['resultado' => 'OK', 'mensaje' => 'Producto eliminado correctamente']);
  
} catch (Exception $e) {
  // Revertimos la transacción en caso de error
  $con->rollback();
  
  http_response_code(400);
  echo json_encode(['resultado' => 'ERROR', 'mensaje' => $e->getMessage()]);
} finally {
  $con->close();
}
?>
