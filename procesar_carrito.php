
<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: carrito.php");
    exit;
}

if (
    empty($_SESSION["csrf_carrito"]) ||
    !hash_equals(
        $_SESSION["csrf_carrito"],
        $_POST["csrf_token"] ?? ""
    )
) {
    http_response_code(403);
    exit("Solicitud no autorizada.");
}

$accion = $_POST["accion"] ?? "";
$id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);

if (!isset($_SESSION["carrito"])) {
    $_SESSION["carrito"] = [];
}

if ($accion === "vaciar") {
    $_SESSION["carrito"] = [];
    header("Location: carrito.php");
    exit;
}

if (!$id) {
    header("Location: carrito.php");
    exit;
}

// Consultar el producto y su stock actual
$consulta = $conexion->prepare(
    "SELECT id, stock FROM productos WHERE id = ?"
);
$consulta->execute([$id]);
$producto = $consulta->fetch();

if ($accion === "eliminar") {
    unset($_SESSION["carrito"][$id]);
} elseif ($producto) {
    $stock = (int)$producto["stock"];

    if ($accion === "agregar" && $stock > 0) {
        $actual = (int)($_SESSION["carrito"][$id] ?? 0);
        $_SESSION["carrito"][$id] = min($actual + 1, $stock);
    }

    if ($accion === "actualizar") {
        $cantidad = filter_var(
            $_POST["cantidad"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 0]]
        );

        if ($cantidad !== false) {
            if ($cantidad === 0 || $stock === 0) {
                unset($_SESSION["carrito"][$id]);
            } else {
                $_SESSION["carrito"][$id] = min($cantidad, $stock);
            }
        }
    }
}

if ($accion === "agregar") {
    header("Location: index.php");
} else {
    header("Location: carrito.php");
}
exit;
