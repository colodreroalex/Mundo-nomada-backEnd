<?php
// Configuración de CORS y manejo de preflight
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir conexión
require_once '../conexion.php';
$conexion = retornarConexion();

// Iniciar sesión
session_start();

// Verificar si la petición es mediante POST y si existe sesión activa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    // Obtener y decodificar los datos enviados
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validar que se recibieron los datos necesarios
    if (isset($data['name']) && isset($data['email'])) {
        $userId = $_SESSION['user']['id'];
        $name = $data['name'];
        $email = $data['email'];
        
        // Validar formato de correo electrónico
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El formato del correo electrónico no es válido.']);
            exit;
        }
        
        // Verificar si el email ya está registrado por otro usuario
        $stmt = $conexion->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está en uso por otro usuario.']);
            exit;
        }
        
        // Obtener la fecha y hora actual
        $currentDate = date('Y-m-d H:i:s');
        
        // Actualizar los datos del usuario
        $updateStmt = $conexion->prepare("UPDATE users SET name = ?, email = ?, updated_at = ? WHERE id = ?");
        $updateStmt->bind_param("sssi", $name, $email, $currentDate, $userId);
        
        if ($updateStmt->execute()) {
            // Actualizar los datos en la sesión
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['updated_at'] = $currentDate;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Perfil actualizado correctamente',
                'updated_at' => $currentDate
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil: ' . $updateStmt->error]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    }
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa o método de solicitud incorrecto']);
}

// Cerrar la conexión
if (isset($conexion)) {
    $conexion->close();
}
?>