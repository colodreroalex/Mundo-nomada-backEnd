
<?php
function retornarConexion() {
    $host = "localhost:3306";
    $user = "root";
    $pass = "";
    $dbname = "mundonomada3";

    try {
        // Intentar crear la conexión
        $conexion = new mysqli($host, $user, $pass, $dbname);

        // Verificar si hubo un error en la conexión
        if ($conexion->connect_error) {
            throw new Exception("Error de conexión: " . $conexion->connect_error);
        }

        return $conexion;

    } catch (Exception $e) {
        // Capturar y manejar el error
        die("Error en la base de datos: " . $e->getMessage());
    }
}


?>
