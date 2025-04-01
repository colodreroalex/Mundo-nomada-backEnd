<?php
// Configuración de CORS
header('Access-Control-Allow-Origin: http://localhost:4200'); 
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Credentials: true');
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json');

// Manejo de la petición OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Este endpoint no requiere verificación de administrador ya que los productos
// necesitan ser accesibles para todos los usuarios de la tienda

require("../conexion.php");
$con = retornarConexion();

try {
  // Modificamos la consulta para incluir un JOIN con la tabla categorías
  // Esto asegura que solo obtenemos productos cuyas categorías existen
  $query = "SELECT p.* FROM Productos p
           INNER JOIN Categorias c ON p.categoriaID = c.CategoriaID";
  $result = $con->query($query);
  
  if (!$result) {
    throw new Exception("Error en la consulta: " . $con->error);
  }
  
  $productos = array();
  while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
  }
  
  echo json_encode([
    'resultado' => 'OK',
    'productos' => $productos
  ]);
  
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'resultado' => 'ERROR',
    'mensaje' => $e->getMessage()
  ]);
} finally {
  $con->close();
}
?>