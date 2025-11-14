<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urban Coop - Cooperativas</title>
    <link rel="stylesheet" href="CSS/indexStyles.css">
</head>
<body>
        
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <img src="IMG/result_descarga.png" alt="Logo" class="logo-image" id="logo-header">
            </div>
            <nav>
                <ul class="nav-menu">
                    <li><a href="#Inicio">Inicio</a></li>
                    <li><a href="#coopretativas">Cooperativas</a></li>
                    <li><a href="#footer-content">Contacto</a></li>
                </ul>
                <button class="menu-toggle" id="menuToggle"> ≡ </button>
            </nav>
            <div class="nav-controls">

                <a href="loginLP.php" class="login-btn">Iniciar Sesión</a>
            </div>
        </div>
    </header>

    <!-- Hero Section with Video -->
    <section id="Inicio" class="hero">
        <div class="video-container">
            <video autoplay muted loop>
                <source name="video-render" src="IMG/untitled_RzEA5X1N.mp4" type="video/mp4">
                Tu navegador no soporta el elemento video.
            </video>
            <div class="video-overlay"></div>
        </div>
    </section>

    <!-- Cooperativas Section -->
    <section id="coopretativas" class="cooperativas">
        <div class="container" >
            <div class="coop-grid">
                <div class="coop-card">
                    <div class="coop-icon">🏗️</div>
                    <div class="coop-type">COOPERATIVAS</div>
                    <h3>En formación y trámite</h3>
                    <p>Para saber más sobre estas cooperativas la visitamos a</p>
                    <a href="#" class="btn">Ver más</a>
                </div>
                <div class="coop-card">
                    <div class="coop-icon">🗗</div>
                    <div class="coop-type">COOPERATIVAS</div>
                    <h3>En obra</h3>
                    <p>Para saber más sobre estas cooperativas la visitamos a</p>
                    <a href="#" class="btn">Ver más</a>
                </div>
                <div class="coop-card">
                    <div class="coop-icon">🏡</div>
                    <div class="coop-type">COOPERATIVAS</div>
                    <h3>Habitadas</h3>
                    <p>Para saber más sobre estas cooperativas la visitamos a</p>
                    <a href="#" class="btn">Ver más</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="footer-content" class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <img src="IMG/descarga.png" alt="" class="footer-logo-image">
                    </div>
                    <p>Facilitamos el acceso a la vivienda a través del sistema cooperativo, promoviendo la solidaridad y el desarrollo comunitario.</p>
                    <p>Tu hogar, nuestra misión.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Enlaces Rápidos</h3>
                    <a href="#cooperativas">Cooperativas</a>
                    <a href="#proceso">Proceso</a>
                    <a href="#contacto">Contacto</a>
                </div>
                
                <div class="footer-section">
                    <h3>Contacto</h3>
                    <p>📍 Andrés Gómez 1770, Montevideo</p>
                    <p>📞 (+598) 091 742 784</p>
                    <p>✉️ equipoORISA@gmail.com</p>
                    <p>🕒 Lunes a Viernes: 9:00 - 17:00</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Urban Coop. Todos los derechos reservados.</p>
                <p>Desarrollado por <span class="orisa-logo">ORISA</span></p>
            </div>
        </div>
    </footer>

    <script src="JSS/index.js"></script>

</body>
</html>