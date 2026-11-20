
<?php
session_start();
require_once "conexion.php";

header("Content-Type: application/json; charset=utf-8");

function responder($exito, $mensaje, $estado = 200) {
    http_response_code($estado);

    echo json_encode([
        "exito" => $exito,
        "mensaje" => $mensaje,
        "cantidad" => array_sum($_SESSION["carrito"] ?? [])
    ]);

    exit;
}

// Comprobar que el usuario ha iniciado sesión
if (!isset($_SESSION["usuario_id"])) {
    responder(false, "Debes iniciar sesión.", 401);
}

// Solo aceptar solicitudes POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método no permitido.", 405);
}

// Verificar el token de seguridad
if (
    empty($_SESSION["csrf_carrito"]) ||
    !hash_equals(
        $_SESSION["csrf_carrito"],
        $_POST["csrf_token"] ?? ""
    )
) {
    responder(false, "Solicitud no autorizada.", 403);
}

// Obtener el producto
$id = filter_var(
    $_POST["id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$id) {
    responder(false, "Producto no válido.", 400);
}

$consulta = $conexion->prepare(
    "SELECT id, stock FROM productos WHERE id = ?"
);
$consulta->execute([$id]);

$producto = $consulta->fetch();

if (!$producto) {
    responder(false, "El producto no existe.", 404);
}

$stock = (int)$producto["stock"];

if ($stock <= 0) {
    responder(false, "Producto agotado.", 409);
}

// Inicializar el carrito
if (!isset($_SESSION["carrito"])) {
    $_SESSION["carrito"] = [];
}

$cantidadActual = (int)($_SESSION["carrito"][$id] ?? 0);

if ($cantidadActual >= $stock) {
    responder(false, "Has alcanzado el stock disponible.", 409);
}

// Añadir una unidad
$_SESSION["carrito"][$id] = $cantidadActual + 1;

responder(true, "Producto añadido al carrito.");
