
<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$nombre = htmlspecialchars(
    $_SESSION["nombre"], ENT_QUOTES, "UTF-8"
);
$esAdmin = $_SESSION["rol"] === "admin";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi panel - TiendaZapatillas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-tienda pagina-interior">
    <?php require "cabecera.php"; ?>
    <main class="contenido-tienda pagina-panel">
        <div class="panel-encabezado">
            <p class="subtitulo">TU ESPACIO</p>
            <h1>Bienvenido, <?= $nombre ?></h1>
            <p><?= $esAdmin ? "Gestiona los datos de tu tienda." : "Consulta el catálogo y gestiona tus compras." ?></p>
        </div>
        <div class="panel-tarjetas">
            <?php if ($esAdmin): ?>
                <a class="panel-tarjeta" href="admin_usuarios.php"><span class="panel-icono">👥</span><h2>Usuarios</h2><p>Crear, editar y gestionar cuentas.</p><span class="panel-enlace">Gestionar usuarios →</span></a>
                <a class="panel-tarjeta" href="admin_productos.php"><span class="panel-icono">👟</span><h2>Productos</h2><p>Gestionar catálogo, precios y stock.</p><span class="panel-enlace">Gestionar productos →</span></a>
            <?php else: ?>
                <a class="panel-tarjeta" href="index.php"><span class="panel-icono">👟</span><h2>Catálogo</h2><p>Explora las zapatillas disponibles.</p><span class="panel-enlace">Ver catálogo →</span></a>
                <a class="panel-tarjeta" href="carrito.php"><span class="panel-icono">🛒</span><h2>Mi carrito</h2><p>Revisa los productos seleccionados.</p><span class="panel-enlace">Ver carrito →</span></a>
                <a class="panel-tarjeta" href="mis_pedidos.php"><span class="panel-icono">📦</span><h2>Mis pedidos</h2><p>Consulta tus compras realizadas.</p><span class="panel-enlace">Ver pedidos →</span></a>
            <?php endif; ?>
        </div>
    </main>
    <script src="menu.js" defer></script>
</body>
</html>
