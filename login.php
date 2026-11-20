
<?php
session_start();
require_once "conexion.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $consulta = $conexion->prepare(
        "SELECT id, nombre, password, rol
         FROM usuarios WHERE email = ?"
    );
    $consulta->execute([$email]);
    $usuario = $consulta->fetch();

    if ($usuario && password_verify($password, $usuario["password"])) {
        session_regenerate_id(true);

        $_SESSION["usuario_id"] = $usuario["id"];
        $_SESSION["nombre"] = $usuario["nombre"];
        $_SESSION["rol"] = $usuario["rol"];

        header("Location: panel.php");
        exit;
    }

    $error = "Correo electrónico o contraseña incorrectos.";
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
    <title>Iniciar sesión - TiendaZapatillas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-tienda pagina-interior">
<?php require "cabecera.php"; ?>
    <main class="contenedor contenedor-acceso">
        <h1>Iniciar sesión</h1>

        <?php if (isset($_GET["registro"])): ?>
            <p class="exito">Cuenta creada correctamente.</p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error"><?= escapar($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Entrar</button>
        </form>

        <p>¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
        <p><a href="index.php">Volver a la tienda</a></p>
    </main>
    <script src="menu.js" defer></script>
</body>
</html>
