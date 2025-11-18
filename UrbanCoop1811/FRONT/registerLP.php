<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - Urban Coop</title>
    <link rel="stylesheet" href="CSS/registerLPStyles.css">
</head>
<body>
    <div class="theme-toggle" id="themeToggle">
        <div class="toggle-icon">
            <span class="sun-icon">☀</span>
            <span class="moon-icon">🌙</span>
        </div>
    </div>

    <div class="login-container">
        <div class="signup-form">
            <h1>Crear Cuenta</h1>
            
            <div id="message-container"></div>
            
            <form id="registerForm" action="API_Usuarios.php/register" method="POST">
                <div class="form-group">
                    <input type="text" name="name" id="name" class="form-input" placeholder="Nombre" required>
                </div>
                
                <div class="form-group">
                    <input type="text" name="surname" id="surname" class="form-input" placeholder="Apellido" required>
                </div>

                <div class="form-group">
                    <input type="password" name="password" id="password" class="form-input" placeholder="Contraseña" required minlength="6">
                </div>
                
                <div class="form-group">
                    <input type="text" name="ci" id="ci" class="form-input" placeholder="Cédula de Identidad" required>
                </div>
                
                <div class="form-group">
                    <input type="email" name="email" id="email" class="form-input" placeholder="Correo Electrónico" required>
                </div>
                
                <div class="form-group">
                    <input type="tel" name="phone" id="phone" class="form-input" placeholder="Número de Teléfono" required>
                </div>
                
                <button type="submit" id="submitBtn" class="signup-btn">
                    <span id="btnText">REGISTRARSE</span>
                </button>
            </form>
        </div>
        
        <div class="welcome-section">
            <h2>¡Bienvenido!</h2>
            <p>Ingresa tus datos personales y comienza tu camino con nosotros</p>
            <a href="loginLP.php" class="signin-btn">INICIAR SESIÓN</a>
        </div>
    </div>

    <script src="JSS/registerLP.js"></script>
</body>
</html>