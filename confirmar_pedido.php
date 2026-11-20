
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

$carrito = $_SESSION["carrito"] ?? [];

if (!$carrito) {
    header("Location: carrito.php");
    exit;
}

try {
    $conexion->beginTransaction();

    $productosPedido = [];
    $totalCentimos = 0;

    // Ordenar los IDs para bloquear siempre en el mismo orden
    ksort($carrito, SORT_NUMERIC);

    foreach ($carrito as $id => $cantidad) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        $cantidad = filter_var(
            $cantidad,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        if (!$id || $cantidad === false) {
            throw new Exception("Producto o cantidad no válida.");
        }

        // Bloquear el producto mientras se procesa la compra
        $consulta = $conexion->prepare(
            "SELECT id, nombre, precio, stock
             FROM productos WHERE id = ? FOR UPDATE"
        );
        $consulta->execute([$id]);
        $producto = $consulta->fetch();

        if (!$producto) {
            throw new Exception("Uno de los productos ya no existe.");
        }

        if ((int)$producto["stock"] < $cantidad) {
            throw new Exception(
                "No hay suficiente stock de " . $producto["nombre"]
            );
        }

        $precio = (string)$producto["precio"];
        $partes = explode(".", $precio);
        $centimos = ((int)$partes[0] * 100)
            + (int)str_pad($partes[1] ?? "0", 2, "0");

        $totalCentimos += $centimos * $cantidad;

        $productosPedido[] = [
            "id" => $id,
            "cantidad" => $cantidad,
            "precio" => $precio
        ];
    }

    $total = number_format($totalCentimos / 100, 2, ".", "");

    // Crear pedido
    $consulta = $conexion->prepare(
        "INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)"
    );
    $consulta->execute([$_SESSION["usuario_id"], $total]);

    $pedidoId = $conexion->lastInsertId();

    foreach ($productosPedido as $producto) {
        // Guardar detalle del pedido
        $consulta = $conexion->prepare(
            "INSERT INTO detalle_pedidos
             (pedido_id, producto_id, cantidad, precio_unitario)
             VALUES (?, ?, ?, ?)"
        );

        $consulta->execute([
            $pedidoId,
            $producto["id"],
            $producto["cantidad"],
            $producto["precio"]
        ]);

        // Descontar stock
        $consulta = $conexion->prepare(
            "UPDATE productos SET stock = stock - ?
             WHERE id = ? AND stock >= ?"
        );

        $consulta->execute([
            $producto["cantidad"],
            $producto["id"],
            $producto["cantidad"]
        ]);

        if ($consulta->rowCount() !== 1) {
            throw new Exception("No se pudo actualizar el stock.");
        }
    }

    $conexion->commit();

    $_SESSION["carrito"] = [];
    $_SESSION["ultimo_pedido"] = $pedidoId;

    header("Location: mis_pedidos.php?confirmado=1");
    exit;

} catch (Exception $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log("Error al confirmar pedido: " . $e->getMessage());

    http_response_code(400);
    echo "<h2>No se pudo confirmar el pedido</h2>";
    echo "<p>Comprueba las existencias y vuelve a intentarlo.</p>";
    echo '<p><a href="carrito.php">Volver al carrito</a></p>';
}
