
<?php
session_start();
require_once "conexion.php";

// Comprobar que el usuario es administrador
if (
    !isset($_SESSION["usuario_id"]) ||
    ($_SESSION["rol"] ?? "") !== "admin"
) {
    http_response_code(403);
    exit("Acceso denegado.");
}

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

// Token de seguridad para los formularios
if (empty($_SESSION["csrf_productos"])) {
    $_SESSION["csrf_productos"] = bin2hex(random_bytes(32));
}

$errores = [];
$mensaje = "";
$editando = null;
$datosFormulario = null;

// Procesar acciones del formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_productos"], $token)) {
        http_response_code(403);
        exit("Solicitud no autorizada.");
    }

    $id = filter_var(
        $_POST["id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($accion === "eliminar") {
        if (!$id) {
            $errores[] = "Producto no válido.";
        } else {
            try {
                $consulta = $conexion->prepare(
                    "DELETE FROM productos WHERE id = ?"
                );
                $consulta->execute([$id]);

                $mensaje = $consulta->rowCount()
                    ? "Producto eliminado correctamente."
                    : "El producto no existe.";
            } catch (PDOException $e) {
                $errores[] = "No se puede eliminar el producto porque está asociado a un pedido.";
            }
        }
    }

    if ($accion === "crear" || $accion === "editar") {
        $nombre = trim($_POST["nombre"] ?? "");
        $marca = trim($_POST["marca"] ?? "");
        $precioTexto = trim($_POST["precio"] ?? "");
        $stockTexto = trim($_POST["stock"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $imagen = trim($_POST["imagen"] ?? "");

        $datosFormulario = [
            "id" => $id,
            "nombre" => $nombre,
            "marca" => $marca,
            "precio" => $precioTexto,
            "stock" => $stockTexto,
            "descripcion" => $descripcion,
            "imagen" => $imagen
        ];

        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 150) {
            $errores[] = "El nombre debe tener entre 2 y 150 caracteres.";
        }

        if (mb_strlen($marca) < 2 || mb_strlen($marca) > 100) {
            $errores[] = "La marca debe tener entre 2 y 100 caracteres.";
        }

        if (
            !preg_match('/^\d{1,8}(\.\d{1,2})?$/', $precioTexto) ||
            (float)$precioTexto <= 0
        ) {
            $errores[] = "Introduce un precio válido mayor que cero.";
        }

        $stock = filter_var(
            $stockTexto,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 0]]
        );

        if ($stock === false) {
            $errores[] = "El stock debe ser un número entero igual o mayor que cero.";
        }

        if (mb_strlen($descripcion) > 2000) {
            $errores[] = "La descripción no puede superar los 2000 caracteres.";
        }

        if (mb_strlen($imagen) > 255) {
            $errores[] = "La ruta de imagen es demasiado larga.";
        }

        // Permitimos rutas relativas dentro del proyecto
        if (
            $imagen !== "" &&
            !preg_match('/^imagenes\/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$/i', $imagen)
        ) {
            $errores[] = "La imagen debe tener una ruta como imagenes/zapatilla.jpg.";
        }

        if ($accion === "editar" && !$id) {
            $errores[] = "Producto no válido.";
        }

        if (!$errores) {
            if ($accion === "crear") {
                $consulta = $conexion->prepare(
                    "INSERT INTO productos
                     (nombre, marca, precio, stock, descripcion, imagen)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                $consulta->execute([
                    $nombre, $marca, $precioTexto, $stock,
                    $descripcion, $imagen
                ]);

                $mensaje = "Producto creado correctamente.";
            } else {
                $consulta = $conexion->prepare(
                    "UPDATE productos
                     SET nombre = ?, marca = ?, precio = ?,
                         stock = ?, descripcion = ?, imagen = ?
                     WHERE id = ?"
                );

                $consulta->execute([
                    $nombre, $marca, $precioTexto, $stock,
                    $descripcion, $imagen, $id
                ]);

                $mensaje = "Producto actualizado correctamente.";
            }

            $datosFormulario = null;
        }
    }
}

// Cargar producto para editar
if (isset($_GET["editar"])) {
    $idEditar = filter_var($_GET["editar"], FILTER_VALIDATE_INT);

    if ($idEditar) {
        $consulta = $conexion->prepare(
            "SELECT * FROM productos WHERE id = ?"
        );
        $consulta->execute([$idEditar]);
        $editando = $consulta->fetch();
    }
}

// Conservar los valores cuando falla una validación
if ($datosFormulario !== null) {
    $editando = $datosFormulario;
}

$consulta = $conexion->query(
    "SELECT * FROM productos ORDER BY id DESC"
);
$productos = $consulta->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de productos - TiendaZapatillas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-tienda">

    <?php require "cabecera.php"; ?>

    <main class="contenedor admin-contenedor">
        <h1>Gestión de productos</h1>
        <p>Administración del catálogo de zapatillas deportivas.</p>

        <?php if ($mensaje): ?>
            <p class="exito"><?= escapar($mensaje) ?></p>
        <?php endif; ?>

        <?php foreach ($errores as $error): ?>
            <p class="error"><?= escapar($error) ?></p>
        <?php endforeach; ?>

        <h2><?= $editando && !empty($editando["id"])
            ? "Modificar producto"
            : "Añadir producto" ?></h2>

        <form method="POST">
            <input type="hidden" name="csrf_token"
                   value="<?= escapar($_SESSION["csrf_productos"]) ?>">

            <input type="hidden" name="accion"
                   value="<?= $editando && !empty($editando["id"])
                       ? "editar" : "crear" ?>">

            <?php if ($editando && !empty($editando["id"])): ?>
                <input type="hidden" name="id"
                       value="<?= (int)$editando["id"] ?>">
            <?php endif; ?>

            <label for="nombre">Nombre del producto</label>
            <input type="text" id="nombre" name="nombre" required
                   maxlength="150"
                   value="<?= escapar($editando["nombre"] ?? "") ?>">

            <label for="marca">Marca</label>
            <input type="text" id="marca" name="marca" required
                   maxlength="100"
                   value="<?= escapar($editando["marca"] ?? "") ?>">

            <label for="precio">Precio (€)</label>
            <input type="number" id="precio" name="precio"
                   min="0.01" step="0.01" required
                   value="<?= escapar($editando["precio"] ?? "") ?>">

            <label for="stock">Unidades disponibles</label>
            <input type="number" id="stock" name="stock"
                   min="0" step="1" required
                   value="<?= escapar($editando["stock"] ?? "0") ?>">

            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion"
                      maxlength="2000" rows="4"><?= escapar($editando["descripcion"] ?? "") ?></textarea>

            <label for="imagen">Ruta de imagen (opcional)</label>
            <input type="text" id="imagen" name="imagen"
                   placeholder="imagenes/zapatilla.jpg"
                   maxlength="255"
                   value="<?= escapar($editando["imagen"] ?? "") ?>">

            <button type="submit">
                <?= $editando && !empty($editando["id"])
                    ? "Guardar cambios" : "Añadir producto" ?>
            </button>

            <?php if ($editando): ?>
                <a href="admin_productos.php">Cancelar edición</a>
            <?php endif; ?>
        </form>

        <h2>Productos registrados</h2>

        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Marca</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$productos): ?>
                        <tr>
                            <td colspan="6">No hay productos registrados.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?= (int)$producto["id"] ?></td>
                            <td><?= escapar($producto["nombre"]) ?></td>
                            <td><?= escapar($producto["marca"]) ?></td>
                            <td><?= number_format((float)$producto["precio"], 2, ",", ".") ?> €</td>
                            <td><?= (int)$producto["stock"] ?></td>
                            <td>
                                <a href="admin_productos.php?editar=<?= (int)$producto["id"] ?>">
                                    Editar
                                </a>

                                <form method="POST" class="form-eliminar"
                                      onsubmit="return confirm('¿Eliminar este producto?');">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= escapar($_SESSION["csrf_productos"]) ?>">
                                    <input type="hidden" name="accion" value="eliminar">
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

        <p><a href="panel.php">Volver al panel</a></p>
    </main>
    <script src="menu.js" defer></script>
</body>
</html>
