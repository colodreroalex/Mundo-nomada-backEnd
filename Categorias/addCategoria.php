<?php

  // Configuración de CORS
  header('Access-Control-Allow-Origin: http://localhost:4200'); 
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Credentials: true");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
  
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

  // Obtener los datos del formulario
  $json = file_get_contents('php://input');
  $params = json_decode($json);

  require("../conexion.php");
  $con = retornarConexion();

  $response = new stdClass();

  if ($params && isset($params->Nombre) && isset($params->Descripcion)) {
      // Primero, verificar si la categoría ya existe
      $stmtCheck = $con->prepare("SELECT COUNT(*) as total FROM Categorias WHERE Nombre = ?");
      $stmtCheck->bind_param("s", $params->Nombre);
      $stmtCheck->execute();
      $result = $stmtCheck->get_result();
      $row = $result->fetch_assoc();
      $stmtCheck->close();

      if ($row['total'] > 0) {
          // La categoría ya existe, se retorna un error
          $response->resultado = 'ERROR';
          $response->mensaje = 'La categoría ya existe';
      } else {
          // Si no existe, se inserta la nueva categoría
          $stmt = $con->prepare("INSERT INTO Categorias (Nombre, Descripcion) VALUES (?, ?)");
          $stmt->bind_param("ss", $params->Nombre, $params->Descripcion);

          if ($stmt->execute()) {
              $response->resultado = 'OK';
              $response->mensaje = 'Categoría registrada correctamente';
          } else {
              $response->resultado = 'ERROR';
              $response->mensaje = 'Error al registrar la categoría';
          }

          $stmt->close();
      }
  } else {
      $response->resultado = 'ERROR';
      $response->mensaje = 'Datos de entrada inválidos';
  }

  $con->close();

  header('Content-Type: application/json');
  echo json_encode($response);
?>