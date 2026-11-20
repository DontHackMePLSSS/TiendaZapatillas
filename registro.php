
<?php
session_start();
require_once "conexion.php";

$errores = [];
$nombre = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
        $errores[] = "El nombre debe tener entre 2 y 100 caracteres.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150) {
        $errores[] = "Introduce un correo electrónico válido.";
    }

    if (strlen($password) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres.";
    }

    if (!$errores) {
        $consulta = $conexion->prepare(
            "SELECT id FROM usuarios WHERE email = ?"
        );
        $consulta->execute([$email]);

        if ($consulta->fetch()) {
            $errores[] = "El correo electrónico ya está registrado.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $consulta = $conexion->prepare(
                "INSERT INTO usuarios (nombre, email, password, rol)
                 VALUES (?, ?, ?, 'cliente')"
            );

            $consulta->execute([$nombre, $email, $hash]);

            header("Location: login.php?registro=ok");
            exit;
        }
    }
}

function escapar($valor) {
    return htmlspecialchars($valor, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro - TiendaZapatillas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-tienda pagina-interior">
<?php require "cabecera.php"; ?>
    <main class="contenedor contenedor-acceso">
        <h1>Crear cuenta</h1>
        <p>Regístrate como cliente de nuestra tienda.</p>

        <?php foreach ($errores as $error): ?>
            <p class="error"><?= escapar($error) ?></p>
        <?php endforeach; ?>

        <form method="POST">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre"
                   maxlength="100" required
                   value="<?= escapar($nombre) ?>">

            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email"
                   maxlength="150" required
                   value="<?= escapar($email) ?>">

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password"
                   minlength="8" required>

            <button type="submit">Registrarse</button>
        </form>

        <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
        <p><a href="index.php">Volver a la tienda</a></p>
    </main>
    <script src="menu.js" defer></script>
</body>
</html>
