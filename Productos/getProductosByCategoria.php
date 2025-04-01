<?php
// Configuración de CORS
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json');

// Manejo de la petición OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Este endpoint no requiere verificación de administrador ya que los productos
// por categoría necesitan ser accesibles para todos los usuarios de la tienda

require("../conexion.php");
$con = retornarConexion();

try {
    if (isset($_GET['categoriaID'])) {
        $categoriaId = intval($_GET['categoriaID']);
        
        // Primero verificar si la categoría existe
        $checkCat = $con->prepare("SELECT COUNT(*) as existe FROM Categorias WHERE CategoriaID = ?");
        $checkCat->bind_param("i", $categoriaId);
        $checkCat->execute();
        $resultCat = $checkCat->get_result();
        $rowCat = $resultCat->fetch_assoc();
        
        if ($rowCat['existe'] == 0) {
            // La categoría no existe
            echo json_encode(['resultado' => 'ERROR', 'mensaje' => 'La categoría no existe']);
            exit;
        }
        
        // Preparar la consulta para evitar inyecciones SQL
        $stmt = $con->prepare("SELECT * FROM Productos WHERE categoriaID = ?");
        $stmt->bind_param("i", $categoriaId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result) {
            throw new Exception("Error en la consulta: " . $stmt->error);
        }
        
        $productos = array();
        while ($fila = $result->fetch_assoc()) {
            $productos[] = $fila;
        }
        
        echo json_encode([
            'resultado' => 'OK',
            'productos' => $productos
        ]);
        
    } else {
        http_response_code(400);
        echo json_encode([
            'resultado' => 'ERROR', 
            'mensaje' => 'No se ha especificado la categoría'
        ]);
    }
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
