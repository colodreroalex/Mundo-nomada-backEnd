<?php

// Configuración de CORS y manejo de preflight
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require("../conexion.php");
$conexion = retornarConexion();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['name']) || empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos']);
    exit;
}

$name = $input['name'];
$email = $input['email'];
$password = $input['password'];

// Verificar si el email ya existe usando sentencias preparadas
$stmtCheck = $conexion->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmtCheck->bind_param("s", $email);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();
if ($resultCheck && $resultCheck->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'El correo electrónico ya está registrado']);
    exit;
}

// Hashear la contraseña
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Iniciar la transacción
$conexion->begin_transaction();

// Insertar el nuevo usuario de forma segura
$stmtInsert = $conexion->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
$stmtInsert->bind_param("sss", $name, $email, $hashedPassword);
if ($stmtInsert->execute()) {
    $userId = $conexion->insert_id;
    // Obtener y devolver los datos del usuario sin la contraseña
    $stmtSelect = $conexion->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
    $stmtSelect->bind_param("i", $userId);
    if ($stmtSelect->execute()) {
        $resultSelect = $stmtSelect->get_result();
        if ($resultSelect && $resultSelect->num_rows > 0) {
            $user = $resultSelect->fetch_assoc();
            // Confirmar la transacción
            $conexion->commit();
            echo json_encode($user);
            exit;
        } else {
            $conexion->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener datos del usuario registrado']);
            exit;
        }
    } else {
        $conexion->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener datos del usuario registrado']);
        exit;
    }
} else {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar el usuario']);
    exit;
}

?>
