
<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["csrf_carrito"])) {
    $_SESSION["csrf_carrito"] = bin2hex(random_bytes(32));
}

$carrito = $_SESSION["carrito"] ?? [];
$productos = [];
$total = 0;

foreach ($carrito as $id => $cantidad) {
    $consulta = $conexion->prepare(
        "SELECT id, nombre, marca, precio, stock
         FROM productos WHERE id = ?"
    );
    $consulta->execute([$id]);
    $producto = $consulta->fetch();

    if (!$producto || (int)$producto["stock"] <= 0) {
        unset($_SESSION["carrito"][$id]);
        continue;
    }

    $cantidad = min(
        (int)$cantidad,
        (int)$producto["stock"]
    );

    if ($cantidad <= 0) {
        unset($_SESSION["carrito"][$id]);
        continue;
    }

    $_SESSION["carrito"][$id] = $cantidad;
    $producto["cantidad"] = $cantidad;
    $producto["subtotal"] =
        (float)$producto["precio"] * $cantidad;

    $total += $producto["subtotal"];
    $productos[] = $producto;
}

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi carrito - TiendaZapatillas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-tienda">

<?php require "cabecera.php"; ?>

<main class="contenido-tienda">
    <section class="pagina-carrito">
        <h1>Mi carrito de compra</h1>
        <p>Revisa los productos antes de confirmar tu pedido.</p>

        <?php if (!$productos): ?>
            <div class="catalogo-vacio">
                <h2>Tu carrito está vacío</h2>
                <p>Todavía no has añadido ninguna zapatilla.</p>
                <a href="index.php" class="boton-hero">
                    Volver al catálogo
                </a>
            </div>
        <?php else: ?>
            <div class="tabla-responsive">
                <table class="tabla-carrito">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td>
                                    <strong><?= escapar($producto["nombre"]) ?></strong>
                                    <br>
                                    <?= escapar($producto["marca"]) ?>
                                </td>
                                <td>
                                    <?= number_format(
                                        (float)$producto["precio"], 2, ",", "."
                                    ) ?> €
                                </td>
                                <td>
                                    <form method="POST"
                                          action="procesar_carrito.php"
                                          class="form-cantidad">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= escapar($_SESSION["csrf_carrito"]) ?>">
                                        <input type="hidden" name="accion"
                                               value="actualizar">
                                        <input type="hidden" name="id"
                                               value="<?= (int)$producto["id"] ?>">

                                        <input type="number" name="cantidad"
                                               min="0"
                                               max="<?= (int)$producto["stock"] ?>"
                                               value="<?= (int)$producto["cantidad"] ?>">

                                        <button type="submit">
                                            Actualizar
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <strong>
                                        <?= number_format(
                                            $producto["subtotal"], 2, ",", "."
                                        ) ?> €
                                    </strong>
                                </td>
                                <td>
                                    <form method="POST"
                                          action="procesar_carrito.php">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= escapar($_SESSION["csrf_carrito"]) ?>">
                                        <input type="hidden" name="accion"
                                               value="eliminar">
                                        <input type="hidden" name="id"
                                               value="<?= (int)$producto["id"] ?>">

                                        <button type="submit" class="btn-eliminar">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="resumen-carrito">
                <h2>Total del pedido:
                    <?= number_format($total, 2, ",", ".") ?> €
                </h2>

                <form method="POST" action="confirmar_pedido.php">
                    <input type="hidden" name="csrf_token"
                           value="<?= escapar($_SESSION["csrf_carrito"]) ?>">
                    <button type="submit" class="boton-confirmar">
                        Confirmar compra
                    </button>
                </form>

                <form method="POST" action="procesar_carrito.php"
                      onsubmit="return confirm('¿Vaciar todo el carrito?');">
                    <input type="hidden" name="csrf_token"
                           value="<?= escapar($_SESSION["csrf_carrito"]) ?>">
                    <input type="hidden" name="accion" value="vaciar">
                    <button type="submit" class="boton-vaciar">
                        Vaciar carrito
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </section>
</main>
<script src="menu.js" defer></script>
</body>
</html>
