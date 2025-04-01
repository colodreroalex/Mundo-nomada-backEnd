<?php 
// Configuración de CORS
header('Access-Control-Allow-Origin: http://localhost:4200'); 
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json');

// Manejo de la petición OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Este endpoint no requiere verificación de administrador ya que los detalles de un producto
// deben ser accesibles para todos los usuarios de la tienda

require("../conexion.php");
$con = retornarConexion();

try {
  if (!isset($_GET['ProductoID'])) {
    throw new Exception("No se ha especificado el ID del producto");
  }

  $productoID = intval($_GET['ProductoID']);

  // Usamos una consulta preparada para evitar inyección SQL
  $stmt = $con->prepare("SELECT * FROM Productos WHERE ProductoID = ?");
  $stmt->bind_param("i", $productoID);
  $stmt->execute();
  $result = $stmt->get_result();

  if (!$result) {
    throw new Exception("Error en la consulta: " . $stmt->error);
  }

  $producto = [];
  
  if ($fila = $result->fetch_assoc()) {
    $producto[] = $fila;
    echo json_encode([
      'resultado' => 'OK',
      'producto' => $producto
    ]);
  } else {
    // Si no se encontró ningún producto con ese ID
    echo json_encode([
      'resultado' => 'ERROR',
      'mensaje' => 'Producto no encontrado'
    ]);
  }

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode([
    'resultado' => 'ERROR',
    'mensaje' => $e->getMessage()
  ]);
} finally {
  if (isset($stmt)) {
    $stmt->close();
  }
  $con->close();
}
?>
