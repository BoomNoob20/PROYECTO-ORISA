<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Elegant Theme Toggle</title>
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
            <h1>Sign in</h1>
            
            <!-- Área para mostrar mensajes -->
            <div id="message-area"></div>
    
            <form id="loginForm">
                <div class="form-group">
                    <input name="email" type="email" class="form-input" placeholder="Email" required>
                </div>
                
                <div class="form-group">
                    <input name="password" type="password" class="form-input" placeholder="Password" required>
                </div>
                
                <div class="forgot-password">
                    <a href="#">Forgot your password?</a>
                </div>
                <div class="submit-btn">
                    <button type="submit" class="login-btn" id="loginButton">
                        SIGN IN
                    </button>
                </div>
            </form>
        </div>
        
        <div class="welcome-section">
            <h2>Welcome Back!</h2>
            <p>To keep connected with us please login with your personal info</p>
            <a href="registerLP.php" class="signup-btn">SIGN UP</a>
        </div>
    </div>

    <script src="JSS/loginPL.js"></script>
</body>
</html>