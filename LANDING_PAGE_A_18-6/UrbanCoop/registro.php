<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/sRegistro.css">
</head>
<body>
    <form class="login-container" method="post" action="registrar.php">
        <h2>Crear cuenta</h2>
        
        <!-- Campos del formulario -->
        <div class="form-group">
            <input type="text" placeholder="Nombre" name="nombre" required>
        </div>
        <div class="form-group">
            <input type="text" placeholder="Apellido" name="apellido" required>
        </div>
        <div class="form-group">
            <input type="text" placeholder="Usuario" name="usuario" required>
        </div>
        <div class="form-group">
            <input type="password" placeholder="Contraseña" name="password" required>
        </div>

        <!-- Mensaje de error -->
        <?php if(isset($_SESSION['error']) && $_SESSION['error'] != ""): ?>
            <div class="error-message"><?php echo $_SESSION['error']; ?></div>
        <?php endif; ?>

        <!-- Botones -->
        <div class="btn-group">
            <button type="submit" class="login-btn">Registrarse</button>
            <a href="index.php" class="cancel-btn">Cancelar</a>
        </div>

        <div class="signup-link">
            ¿Ya tienes cuenta? <a href="iniSesion.php">Inicia sesión</a>
        </div>
    </form>
</body>
</html>
