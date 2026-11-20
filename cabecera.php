
<?php
// La página que incluye esta cabecera debe haber iniciado sesión
// con session_start() antes de llegar aquí.

$sesionIniciada = isset($_SESSION["usuario_id"]);
$esAdministrador = ($_SESSION["rol"] ?? "") === "admin";
$cantidadCarrito = array_sum($_SESSION["carrito"] ?? []);
?>

<header class="cabecera-tienda">
    <div class="barra-tienda">

        <a href="index.php" class="logo-tienda">
            Tienda<span>Zapatillas</span>
        </a>

        <nav class="navegacion-tienda" aria-label="Navegación principal">

            <a href="index.php#catalogo">Catálogo</a>

            <?php if ($sesionIniciada): ?>

                <a href="carrito.php" class="enlace-carrito">
                    🛒 Mi carrito
                    <span class="contador-carrito" id="contadorCarrito">
                        <?= (int)$cantidadCarrito ?>
                    </span>
                </a>

                <?php if ($esAdministrador): ?>

                    <div class="menu-desplegable">
                        <button type="button"
                                class="boton-desplegable"
                                aria-expanded="false"
                                aria-controls="opciones-panel">
                            Mi panel ▾
                        </button>

                        <div class="opciones-desplegable" id="opciones-panel">
                            <a href="panel.php">Panel principal</a>
                            <a href="admin_usuarios.php">Gestionar usuarios</a>
                            <a href="admin_productos.php">Gestionar productos</a>
                        </div>
                    </div>

                <?php else: ?>

    <a href="mis_pedidos.php">Mis pedidos</a>

    <div class="menu-desplegable">
            <button type="button"
                class="boton-desplegable"
                aria-expanded="false"
                aria-controls="opciones-cuenta">
                Mi cuenta ▾
            </button>

                <div class="opciones-desplegable" id="opciones-cuenta">
                    <a href="panel.php">Mi panel</a>
                    <a href="logout.php">Cerrar sesión</a>
                </div>
                </div>

                <?php endif; ?>

                <?php if ($esAdministrador): ?>
                    <a href="logout.php">Cerrar sesión</a>
                <?php endif; ?>

                <?php else: ?>

                <a href="login.php">Iniciar sesión</a>
                <a href="registro.php" class="enlace-registro">
                    Registrarse
                </a>

            <?php endif; ?>

        </nav>
    </div>
</header>
