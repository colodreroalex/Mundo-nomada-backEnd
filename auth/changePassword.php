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
    if (isset($data['oldPassword']) && isset($data['newPassword'])) {
        $userId = $_SESSION['user']['id'];
        $oldPassword = $data['oldPassword'];
        $newPassword = $data['newPassword'];
        
        // Validar longitud de contraseña
        if (strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
            exit;
        }
        
        // Verificar la contraseña actual
        $stmt = $conexion->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $userData = $result->fetch_assoc();
            $hashedPasswordFromDB = $userData['password'];
            
            // Verificar si la contraseña actual es correcta - usando password_verify nativa de PHP
            if (password_verify($oldPassword, $hashedPasswordFromDB)) {
                // Hash de la nueva contraseña - usando password_hash nativa de PHP
                $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                
                // Obtener la fecha actual
                $fechaActualizacion = date('Y-m-d H:i:s');
                
                // Actualizar la contraseña y la fecha de actualización
                $updateStmt = $conexion->prepare("UPDATE users SET password = ?, password_updated_at = ? WHERE id = ?");
                $updateStmt->bind_param("ssi", $hashedNewPassword, $fechaActualizacion, $userId);
                
                if ($updateStmt->execute()) {
                    // Actualizar la información en la sesión
                    $_SESSION['user']['password_updated_at'] = $fechaActualizacion;
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Contraseña actualizada correctamente',
                        'password_updated_at' => $fechaActualizacion
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña: ' . $updateStmt->error]);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'La contraseña actual no es correcta']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
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