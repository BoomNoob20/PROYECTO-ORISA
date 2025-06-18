<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urban Coop - Cooperativas</title>
    <link rel="stylesheet" href="css/sIndex.css">
</head>
<body>
        
 <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <div class="logo-icon"></div>
                URBAN COOP
            </div>
            <nav>
                <ul class="nav-menu">
                    <li><a href="#inicio">Inicio</a></li>
                    <li><a href="#servicios">Servicios</a></li>
                    <li><a href="#productos">Productos</a></li>
                    <li><a href="#clientes">Clientes</a></li>
                    <li><a href="#empresa">Empresa</a></li>
                    <li><a href="#contacto">Contacto</a></li>
                    <li><a href="iniSesion.php" class="login-btn">Iniciar Sesión</a></li>
                </ul>
                <button class="menu-toggle">≡ Menú</button>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="gallery-container">
            <!-- Imágenes de la galería -->
            <div class="gallery-image active"><img src="IMG/4294_0.jpg" alt=""></div>
            <div class="gallery-image"><img src="IMG/7ad9b351-3294-4068-9f79-e38b19094afd_16-9-aspect-ratio_default_0.jpg" alt=""></div>
            <div class="gallery-image"><img src="IMG/20190923-vivienda-2.jpg" alt=""></div>
            <div class="gallery-image"><img src="IMG/coop_vivienda2-copy.jpg" alt=""></div>
            

            <!-- Botones de navegación -->
            <button class="gallery-nav prev" onclick="changeImage(-1)">‹</button>
            <button class="gallery-nav next" onclick="changeImage(1)">›</button>

            <!-- Indicadores de puntos -->
            <div class="gallery-dots">
                <span class="gallery-dot active" onclick="currentSlide(1)"></span>
                <span class="gallery-dot" onclick="currentSlide(2)"></span>
                <span class="gallery-dot" onclick="currentSlide(3)"></span>
                <span class="gallery-dot" onclick="currentSlide(4)"></span>
            </div>
        </div>
    </section>

    <!-- Cooperativas Section -->
    <section class="cooperativas">
        <div class="container">
            <div class="coop-grid">
                <div class="coop-card">
                    <div class="coop-icon">🏠</div>
                    <div class="coop-type">COOPERATIVAS</div>
                    <h3>En formación y trámite</h3>
                    <p>Para saber más sobre estas cooperativas la visitamos a</p>
                    <a href="#" class="btn">Ver más</a>
                </div>
                <div class="coop-card">
                    <div class="coop-icon">🏠</div>
                    <div class="coop-type">COOPERATIVAS</div>
                    <h3>En obra</h3>
                    <p>Para saber más sobre estas cooperativas la visitamos a</p>
                    <a href="#" class="btn">Ver más</a>
                </div>
                <div class="coop-card">
                    <div class="coop-icon">🏠</div>
                    <div class="coop-type">COOPERATIVAS</div>
                    <h3>Habitadas</h3>
                    <p>Para saber más sobre estas cooperativas la visitamos a</p>
                    <a href="#" class="btn">Ver más</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">

        <!-- CTA Section -->
        <div class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2>¿Interesado en formar una cooperativa?</h2>
                    <p>Te acompañamos en todo el proceso desde la idea inicial hasta la entrega de llaves</p>
                    <div class="cta-buttons">
                        <a href="#contacto" class="btn btn-primary">Solicitar Información</a>
                        <a href="tel:+59829999999" class="btn btn-secondary">Llamar Ahora</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-logo">
                    <div class="logo-icon"></div>
                    <span style="color: white; font-weight: bold; margin-left: 10px;">URBAN COOP</span>
                </div>
                <h3>Contactanos</h3>
                <p>Dirección:<br>Montevideo, Uruguay</p>
                <p>Teléfono:<br>+598 2XXX XXXX</p>
            </div>
            <div class="footer-section">
                <h3>Enlaces</h3>
                <a href="#">Inicio</a>
                <a href="#">Servicios</a>
                <a href="#">Productos</a>
                <a href="#">Clientes</a>
                <a href="#">Empresa</a>
                <a href="#">Contacto</a>
            </div>
            <div class="footer-section">
                <h3>Servicios</h3>
                <a href="#">Cooperativas en formación</a>
                <a href="#">Cooperativas en obra</a>
                <a href="#">Cooperativas habitadas</a>
                <a href="#">Asesoramiento legal</a>
            </div>
            <div class="footer-section">
                <h3>Redes Sociales</h3>
                <a href="#">Facebook</a>
                <a href="#">Instagram</a>
                <a href="#">Twitter</a>
                <a href="#">LinkedIn</a>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="orisa-logo">ORISA</div>
        </div>
    </footer>

    <script>
        let currentImageIndex = 0;
        const images = document.querySelectorAll('.gallery-image');
        const dots = document.querySelectorAll('.gallery-dot');
        const totalImages = images.length;

        function showImage(index) {
            // Ocultar todas las imágenes
            images.forEach(img => img.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Mostrar la imagen actual
            images[index].classList.add('active');
            dots[index].classList.add('active');
        }

        function changeImage(direction) {
            currentImageIndex += direction;
            
            if (currentImageIndex >= totalImages) {
                currentImageIndex = 0;
            } else if (currentImageIndex < 0) {
                currentImageIndex = totalImages - 1;
            }
            
            showImage(currentImageIndex);
        }

        function currentSlide(index) {
            currentImageIndex = index - 1;
            showImage(currentImageIndex);
        }

        // Auto-cambio de imágenes cada 5 segundos
        setInterval(() => {
            changeImage(1);
        }, 10000);

        // Asegurar que la galería funcione al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            showImage(0);
        });
    </script>
</body>
</html>

