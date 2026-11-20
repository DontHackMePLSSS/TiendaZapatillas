
<?php
// Conexión con la base de datos de la tienda
$servidor = "localhost";
$usuario = "root";
$contrasena = "";
$baseDatos = "tienda_zapatillas";

try {
    $conexion = new PDO(
        "mysql:host=$servidor;dbname=$baseDatos;charset=utf8mb4",
        $usuario,
        $contrasena
    );

    // Mostrar errores mediante excepciones
    $conexion->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // Obtener los resultados como arrays asociativos
    $conexion->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    http_response_code(500);
    exit("No se ha podido conectar con la base de datos.");
}
?>
