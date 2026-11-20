
<?php
session_start();
require_once "conexion.php";
if (empty($_SESSION["csrf_carrito"])) {
    $_SESSION["csrf_carrito"] = bin2hex(random_bytes(32));
}

// Recuperar los productos desde MySQL
$consulta = $conexion->query(
    "SELECT id, nombre, marca, precio, stock, descripcion, imagen
     FROM productos
     ORDER BY id DESC"
);

$productos = $consulta->fetchAll();

// Comprobar si existe una sesión iniciada
$usuario = isset($_SESSION["usuario_id"]);
$nombreUsuario = $_SESSION["nombre"] ?? "";
$esAdmin = ($_SESSION["rol"] ?? "") === "admin";

function escapar($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TiendaZapatillas | Catálogo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-tienda">

    <?php require "cabecera.php"; ?>

    <main class="contenido-tienda">

        <section class="hero-tienda">
            <span class="etiqueta-hero">NUEVA COLECCIÓN</span>

            <h1>Encuentra tus próximas zapatillas</h1>

            <p>
                Descubre nuestro catálogo de zapatillas deportivas.
                Comodidad, estilo y rendimiento en un mismo lugar.
            </p>

            <a href="#catalogo" class="boton-hero">
                Ver colección
            </a>
        </section>

        <section id="catalogo" class="seccion-catalogo">
            <div class="encabezado-catalogo">
                <div>
                    <span class="subtitulo">NUESTROS PRODUCTOS</span>
                    <h2>Catálogo de zapatillas</h2>
                </div>

                <p>
                    <?= count($productos) ?>
                    productos disponibles en el catálogo
                </p>
            </div>

            <?php if (!$productos): ?>

                <div class="catalogo-vacio">
                    <h3>Todavía no hay zapatillas disponibles</h3>
                    <p>
                        Próximamente añadiremos nuevos productos.
                    </p>
                </div>

            <?php else: ?>

                <div class="grid-productos">

                    <?php foreach ($productos as $producto): ?>

                        <article class="tarjeta-producto">

                            <div class="imagen-producto">
                                <?php
                                $rutaImagen = $producto["imagen"] ?? "";

                                // Solo permitir imágenes locales del proyecto
                                $imagenValida = preg_match(
                                    '/^imagenes\/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$/i',
                                    $rutaImagen
                                );
                                ?>

                                <?php if (
                                    $imagenValida &&
                                    is_file(__DIR__ . "/" . $rutaImagen)
                                ): ?>
                                    <img
                                        src="<?= escapar($rutaImagen) ?>"
                                        alt="<?= escapar($producto["nombre"]) ?>"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <div class="sin-imagen">
                                        <span>👟</span>
                                        <p>Imagen no disponible</p>
                                    </div>
                                <?php endif; ?>

                                <?php if ((int)$producto["stock"] === 0): ?>
                                    <span class="etiqueta-agotado">
                                        Agotado
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="info-producto">
                                <span class="marca-producto">
                                    <?= escapar($producto["marca"]) ?>
                                </span>

                                <h3>
                                    <?= escapar($producto["nombre"]) ?>
                                </h3>

                                <p class="descripcion-producto">
                                    <?= escapar($producto["descripcion"]) ?>
                                </p>

                                <div class="precio-producto">
                                    <?= number_format(
                                        (float)$producto["precio"],
                                        2,
                                        ",",
                                        "."
                                    ) ?> €
                                </div>

                                <p class="stock-producto">
                                    <?php if ((int)$producto["stock"] > 0): ?>
                                        <span class="punto-stock"></span>
                                        <?= (int)$producto["stock"] ?>
                                        unidades disponibles
                                    <?php else: ?>
                                        Sin existencias
                                    <?php endif; ?>
                                </p>

                                <?php if ((int)$producto["stock"] > 0): ?>
                                    
                                <?php if ($usuario): ?>
                                    <form method="POST"
                                        action="procesar_carrito.php"
                                        class="form-agregar-carrito">
                                        <input type="hidden" name="csrf_token"
                                             value="<?= escapar($_SESSION["csrf_carrito"]) ?>">
                                        <input type="hidden" name="accion" value="agregar">
                                        <input type="hidden" name="id"
                                            value="<?= (int)$producto["id"] ?>">

                                        <button type="submit" class="boton-carrito">
                                         Añadir al carrito
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="login.php" class="enlace-carrito-login">
                                        Inicia sesión para comprar
                                    </a>
                                <?php endif; ?>

                                <?php else: ?>
                                    <button
                                        class="boton-carrito agotado"
                                        disabled
                                    >
                                        Producto agotado
                                    </button>
                                <?php endif; ?>
                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>
        </section>

    </main>

    <footer class="footer-tienda">
        <p>© <?= date("Y") ?> TiendaZapatillas</p>
        <p>Proyecto de Desarrollo Web en Entorno Servidor</p>
    </footer>
<script src="carrito.js" defer></script>
<script src="menu.js" defer></script>
</body>
</html>


