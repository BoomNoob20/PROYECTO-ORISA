<?php
session_start();

function connectToDatabase() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "pointsup_login";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    return $conn;
}

function validateUser ($usuario, $contrasena) {
    $conn = connectToDatabase();
    $stmt = $conn->prepare("SELECT id, usr_name, usr_pass, is_admin FROM usuario WHERE usr_name = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $conn->close();
    return $result;
}

$errorMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    if (empty($usuario) || empty($contrasena)) {
        $errorMessage = "Por favor, complete todos los campos.";
    } else {
        $result = validateUser ($usuario, $contrasena);
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if ($contrasena === $row['usr_pass']) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['usuario'] = $row['usr_name'];
                $_SESSION['apellido'] = $row['apellido'];
                $_SESSION['is_admin'] = $row['is_admin'];
                header("Location: perfil.php");
                exit;
            } else {
                $errorMessage = "Contraseña incorrecta.";
            }
        } else {
            $errorMessage = "Usuario no encontrado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="css/sLogin.css">
<title>Inicio de sesión</title>

</head>
<body>
<div class="login-container">
  <h2>Iniciar sesión</h2>
  <?php if ($errorMessage): ?>
    <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
  <?php endif; ?>
  <form method="post" action="">
    <div class="form-group">
      <input type="text" name="usuario" placeholder="Usuario" value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <input type="password" name="contrasena" placeholder="Contraseña" required>
    </div>
    <button type="submit" class="login-btn">Entrar</button>
  </form>
  <p class="signup-link">
    ¿No tenés cuenta?
    <a href="registrar.php">Regístrate</a>
  </p>
</div>
</body>
</html>

