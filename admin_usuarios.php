
<?php
session_start();
require_once "conexion.php";

// Solo los administradores pueden acceder
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

if (empty($_SESSION["csrf_usuarios"])) {
    $_SESSION["csrf_usuarios"] = bin2hex(random_bytes(32));
}

$errores = [];
$mensaje = "";
$editando = null;

// Procesar creación, modificación y eliminación
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    (!isset($_POST["csrf_token"]) ||
     !is_string($_POST["csrf_token"]) ||
     !hash_equals($_SESSION["csrf_usuarios"], $_POST["csrf_token"]))) {
    http_response_code(403);
    exit("Solicitud no autorizada. Vuelve a cargar la página.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";
    $id = filter_var(
        $_POST["id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($accion === "eliminar") {
        if (!$id) {
            $errores[] = "Usuario no válido.";
        } elseif ($id === (int)$_SESSION["usuario_id"]) {
            $errores[] = "No puedes eliminar tu propia cuenta.";
        } else {
            $consulta = $conexion->prepare(
                "DELETE FROM usuarios WHERE id = ?"
            );

            try {
                $consulta->execute([$id]);
                $mensaje = "Usuario eliminado correctamente.";
            } catch (PDOException $e) {
                $errores[] = "No se puede eliminar este usuario porque tiene datos relacionados.";
            }
        }
    }

    if ($accion === "crear" || $accion === "editar") {
        $nombre = trim($_POST["nombre"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $rol = $_POST["rol"] ?? "";

        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
            $errores[] = "El nombre debe tener entre 2 y 100 caracteres.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo electrónico no es válido.";
        }

        if (!in_array($rol, ["admin", "cliente"], true)) {
            $errores[] = "Selecciona un rol válido.";
        }

        if ($accion === "crear" && strlen($password) < 8) {
            $errores[] = "La contraseña debe tener al menos 8 caracteres.";
        }

        if ($accion === "editar" && $password !== "" &&
            strlen($password) < 8) {
            $errores[] = "La nueva contraseña debe tener al menos 8 caracteres.";
        }

        if ($accion === "editar" && !$id) {
            $errores[] = "Usuario no válido.";
        }

        if ($accion === "editar" &&
            $id === (int)$_SESSION["usuario_id"] &&
            $rol !== "admin") {
            $errores[] = "No puedes quitarte tu propio rol de administrador.";
        }

        if (!$errores) {
            $consulta = $conexion->prepare(
                "SELECT id FROM usuarios WHERE email = ? AND id <> ?"
            );
            $consulta->execute([$email, $accion === "editar" ? $id : 0]);

            if ($consulta->fetch()) {
                $errores[] = "Ya existe un usuario con ese correo.";
            }
        }

        if (!$errores) {
            if ($accion === "crear") {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $consulta = $conexion->prepare(
                    "INSERT INTO usuarios (nombre, email, password, rol)
                     VALUES (?, ?, ?, ?)"
                );
                $consulta->execute([$nombre, $email, $hash, $rol]);

                $mensaje = "Usuario creado correctamente.";
            } else {
                $consulta = $conexion->prepare(
                    "UPDATE usuarios SET nombre = ?, email = ?, rol = ?
                     WHERE id = ?"
                );
                $consulta->execute([$nombre, $email, $rol, $id]);

                if ($password !== "") {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $consulta = $conexion->prepare(
                        "UPDATE usuarios SET password = ? WHERE id = ?"
                    );
                    $consulta->execute([$hash, $id]);
                }

                if ($id === (int)$_SESSION["usuario_id"]) {
                    $_SESSION["nombre"] = $nombre;
                }

                $mensaje = "Usuario modificado correctamente.";
            }
        }
    }
}

// Cargar usuario para editar
if (isset($_GET["editar"])) {
    $idEditar = filter_var($_GET["editar"], FILTER_VALIDATE_INT);

    if ($idEditar) {
        $consulta = $conexion->prepare(
            "SELECT id, nombre, email, rol FROM usuarios WHERE id = ?"
        );
        $consulta->execute([$idEditar]);
        $editando = $consulta->fetch();
    }
}

// Consultar todos los usuarios
$consulta = $conexion->query(
    "SELECT id, nombre, email, rol FROM usuarios ORDER BY id"
);
$usuarios = $consulta->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de usuarios</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-tienda pagina-interior">
<?php require "cabecera.php"; ?>
    <main class="contenedor admin-contenedor">
        <p class="subtitulo">ADMINISTRACIÓN</p>
        <h1>Gestión de usuarios</h1>
        <p>Administración de clientes y administradores.</p>

        <?php if ($mensaje): ?>
            <p class="exito"><?= escapar($mensaje) ?></p>
        <?php endif; ?>

        <?php foreach ($errores as $error): ?>
            <p class="error"><?= escapar($error) ?></p>
        <?php endforeach; ?>

        <h2><?= $editando ? "Modificar usuario" : "Crear usuario" ?></h2>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= escapar($_SESSION["csrf_usuarios"]) ?>">
            <input type="hidden" name="accion"
                   value="<?= $editando ? 'editar' : 'crear' ?>">

            <?php if ($editando): ?>
                <input type="hidden" name="id"
                       value="<?= (int)$editando["id"] ?>">
            <?php endif; ?>

            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" required
                   maxlength="100"
                   value="<?= escapar($editando["nombre"] ?? "") ?>">

            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" required
                   value="<?= escapar($editando["email"] ?? "") ?>">

            <label for="password">
                <?= $editando ? "Nueva contraseña (opcional)" : "Contraseña" ?>
            </label>
            <input type="password" id="password" name="password"
                   <?= $editando ? "" : "required" ?>>

            <label for="rol">Tipo de usuario</label>
            <select id="rol" name="rol" required>
                <option value="cliente"
                    <?= ($editando["rol"] ?? "") === "cliente" ? "selected" : "" ?>>
                    Cliente
                </option>
                <option value="admin"
                    <?= ($editando["rol"] ?? "") === "admin" ? "selected" : "" ?>>
                    Administrador
                </option>
            </select>

            <button type="submit">
                <?= $editando ? "Guardar cambios" : "Crear usuario" ?>
            </button>

            <?php if ($editando): ?>
                <a href="admin_usuarios.php">Cancelar edición</a>
            <?php endif; ?>
        </form>

        <h2>Usuarios registrados</h2>

        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= (int)$usuario["id"] ?></td>
                            <td><?= escapar($usuario["nombre"]) ?></td>
                            <td><?= escapar($usuario["email"]) ?></td>
                            <td><?= escapar($usuario["rol"]) ?></td>
                            <td>
                                <a href="admin_usuarios.php?editar=<?= (int)$usuario["id"] ?>">
                                    Editar
                                </a>

                                <?php if ((int)$usuario["id"] !== (int)$_SESSION["usuario_id"]): ?>
                                    <form method="POST" class="form-eliminar"
                                          onsubmit="return confirm('¿Eliminar este usuario?');">
                                        <input type="hidden" name="csrf_token" value="<?= escapar($_SESSION["csrf_usuarios"]) ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id"
                                               value="<?= (int)$usuario["id"] ?>">
                                        <button type="submit" class="btn-eliminar">
                                            Eliminar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p><a href="panel.php">← Volver al panel</a></p>
    </main>
    <script src="menu.js" defer></script>
</body>
</html>
