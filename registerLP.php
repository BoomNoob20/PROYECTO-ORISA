<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Elegant Theme Toggle</title>
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
            <h1>Create Account</h1>
            
            <div id="message-container"></div>
            
            <form id="registerForm" action="API_Usuarios.php/register" method="POST">
                <div class="form-group">
                    <input type="text" name="name" id="name" class="form-input" placeholder="Name" required>
                </div>
                
                <div class="form-group">
                    <input type="text" name="surname" id="surname" class="form-input" placeholder="Surname" required>
                </div>

                <div class="form-group">
                    <input type="password" name="password" id="password" class="form-input" placeholder="Password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <input type="text" name="ci" id="ci" class="form-input" placeholder="CI" required>
                </div>
                
                <div class="form-group">
                    <input type="email" name="email" id="email" class="form-input" placeholder="Email" required>
                </div>
                
                <div class="form-group">
                    <input type="tel" name="phone" id="phone" class="form-input" placeholder="Phone Number" required>
                </div>
                
                <button type="submit" id="submitBtn" class="signup-btn">
                    <span id="btnText">SIGN UP</span>
                </button>
            </form>
        </div>
        
        <div class="welcome-section">
            <h2>Welcome!</h2>
            <p>Enter your personal details and start your journey with us</p>
            <a href="loginLP.php" class="signin-btn">SIGN IN</a>
        </div>
    </div>

    <script src="JSS/registerLP.js"></script>
</body>
</html>