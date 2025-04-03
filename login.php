<?php
session_start();
require 'db.php'; // Archivo de conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    $stmt = $conn->prepare("SELECT id_usuario, contrasena, intentos_fallidos, bloqueado FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id_usuario, $hash, $intentos, $bloqueado);
        $stmt->fetch();
        
        if ($bloqueado) {
            $error = "Usuario bloqueado. Contacte con el administrador.";
        } else {
            if (password_verify($contrasena, $hash)) {
                $_SESSION['usuario'] = $usuario;
                $conn->query("UPDATE usuarios SET intentos_fallidos = 0 WHERE id_usuario = $id_usuario");
                $conn->query("INSERT INTO logs_sesiones (id_usuario, ip) VALUES ($id_usuario, '" . $_SERVER['REMOTE_ADDR'] . "')");
                header("Location: dashboard.php");
                exit();
            } else {
                $intentos++;
                if ($intentos >= 3) {
                    $conn->query("UPDATE usuarios SET bloqueado = TRUE WHERE id_usuario = $id_usuario");
                    $error = "Usuario bloqueado por múltiples intentos fallidos.";
                } else {
                    $conn->query("UPDATE usuarios SET intentos_fallidos = $intentos WHERE id_usuario = $id_usuario");
                    $error = "Usuario o contraseña incorrectos.";
                }
            }
        }
    } else {
        $error = "Usuario no encontrado.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Legumbrería</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; text-align: center; padding: 50px; }
        .login-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); display: inline-block; }
        input, button { display: block; margin: 10px auto; padding: 10px; width: 80%; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="usuario" placeholder="Usuario" required>
            <input type="password" name="contrasena" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
