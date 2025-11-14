<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Urban Coop</title>
    <link rel="stylesheet" href="CSS/loginLPStyles.css">
</head>
<body>
    <div class="theme-toggle" id="themeToggle">
        <div class="toggle-icon">
            <span class="sun-icon">☀</span>
            <span class="moon-icon">🌙</span>
        </div>
    </div>

    <div class="login-container">
        <div class="login-form">
            <h1>Iniciar Sesión</h1>
            
            <!-- Área para mostrar mensajes -->
            <div id="message-area"></div>
    
            <form id="loginForm">
                <div class="form-group">
                    <input name="email" type="email" class="form-input" placeholder="Correo Electrónico" required>
                </div>
                
                <div class="form-group">
                    <input name="password" type="password" class="form-input" placeholder="Contraseña" required>
                </div>
                
                <div class="forgot-password">
                    <a href="#">¿Olvidaste tu contraseña?</a>
                </div>
                <div class="submit-btn">
                    <button type="submit" class="login-btn" id="loginButton">
                        INGRESAR
                    </button>
                </div>
            </form>
        </div>
        
        <div class="welcome-section">
            <h2>¡Bienvenido de Nuevo!</h2>
            <p>Para mantenerte conectado con nosotros, por favor inicia sesión con tu información personal</p>
            <a href="registerLP.php" class="signup-btn">REGISTRARSE</a>
        </div>
    </div>

    <script src="JSS/loginPL.js"></script>
</body>
</html>