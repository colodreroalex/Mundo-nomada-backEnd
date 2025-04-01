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

require("../conexion.php");
$con = retornarConexion();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->CategoriaID)) {
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'ID de categoría no especificado']);
    exit();
}

$categoriaID = intval($data->CategoriaID);

$sql = "DELETE FROM categorias WHERE CategoriaID = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $categoriaID);
$stmt->execute();

if($stmt->affected_rows > 0) {
    echo json_encode(['resultado' => 'OK', 'mensaje' => 'Categoría eliminada correctamente']);
} else {
    echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'No se pudo eliminar la categoría o no existe']);
}

$stmt->close();
$con->close();
?>