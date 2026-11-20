
<?php
session_start();

// Vaciar los datos de la sesión
$_SESSION = [];

// Eliminar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $parametros = session_get_cookie_params();

    setcookie(session_name(), "", [
        "expires" => time() - 3600,
        "path" => $parametros["path"],
        "domain" => $parametros["domain"],
        "secure" => $parametros["secure"],
        "httponly" => $parametros["httponly"],
        "samesite" => $parametros["samesite"] ?? "Lax"
    ]);
}

session_destroy();

header("Location: index.php");
exit;
?>
