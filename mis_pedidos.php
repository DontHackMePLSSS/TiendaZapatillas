
<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$consulta = $conexion->prepare(
    "SELECT id, fecha, total
     FROM pedidos
     WHERE usuario_id = ?
     ORDER BY fecha DESC, id DESC"
);
$consulta->execute([$_SESSION["usuario_id"]]);
$pedidos = $consulta->fetchAll();

$consultaDetalle = $conexion->prepare(
    "SELECT d.cantidad, d.precio_unitario, p.nombre
     FROM detalle_pedidos d
     INNER JOIN productos p ON p.id = d.producto_id
     WHERE d.pedido_id = ?"
);

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis pedidos - TiendaZapatillas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-tienda">

<?php require "cabecera.php"; ?>

<main class="contenido-tienda">
    <section class="pagina-carrito">
        <h1>Mis pedidos</h1>

        <?php if (
            isset($_GET["confirmado"], $_SESSION["ultimo_pedido"]) &&
            $_GET["confirmado"] === "1"
        ): ?>
            <p class="exito">
                Compra confirmada correctamente.
                Número de pedido:
                #<?= (int)$_SESSION["ultimo_pedido"] ?>
            </p>
            <?php unset($_SESSION["ultimo_pedido"]); ?>
        <?php endif; ?>

        <?php if (!$pedidos): ?>
            <p>Todavía no has realizado ninguna compra.</p>
        <?php endif; ?>

        <?php foreach ($pedidos as $pedido): ?>
            <div class="tarjeta-pedido">
                <h2>Pedido #<?= (int)$pedido["id"] ?></h2>

                <p>
                    Fecha:
                    <?= escapar($pedido["fecha"]) ?>
                </p>

                <p>
                    <strong>Total:
                        <?= number_format(
                            (float)$pedido["total"], 2, ",", "."
                        ) ?> €
                    </strong>
                </p>

                <?php
                $consultaDetalle->execute([$pedido["id"]]);
                $detalles = $consultaDetalle->fetchAll();
                ?>

                <ul>
                    <?php foreach ($detalles as $detalle): ?>
                        <li>
                            <?= escapar($detalle["nombre"]) ?>
                            — <?= (int)$detalle["cantidad"] ?> unidad(es)
                            × <?= number_format(
                                (float)$detalle["precio_unitario"],
                                2, ",", "."
                            ) ?> €
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </section>
</main>
<script src="menu.js" defer></script>
</body>
</html>
