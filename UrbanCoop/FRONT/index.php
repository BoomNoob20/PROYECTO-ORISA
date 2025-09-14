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
                    <li><a href="#Novedades">Novedades</a></li>
                    <li><a href="#testimonios">Clientes</a></li>
                    <li><a href="#footer-content">Contacto</a></li>
                </ul>
                <button class="menu-toggle" id="menuToggle"> ≡ </button>
            </nav>
            <div class="nav-controls">
                <button class="dark-mode-toggle" id="darkModeToggle">
                    <svg id="lightIcon" viewBox="0 0 24 24">
                        <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"/>
                    </svg>
                    <svg id="darkIcon" viewBox="0 0 24 24" style="display: none;">
                        <path d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z"/>
                    </svg>
                </button>
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
    <section class="cooperativas">
        <div class="container">
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

    <!-- Novedades Section -->
    <section id="Novedades" class="novedades">
        <div class="container">
            <h2 class="section-title">Novedades</h2>
            <p class="section-subtitle">En esta sección encontrarás las principales novedades relacionadas con el tema de cooperativas, acciones, publicaciones, plazas, convocatorias de vivienda, cooperativas, actividades de desarrollo, eventos importantes.</p>
            
            <div class="news-grid">
                <div class="news-column">
                    <div class="news-item">
                        <div class="news-image">
                            <img src="IMG/result_images.jpeg" alt="FUCVAM" class="news-img">
                        </div>
                        <div class="news-content">
                            <h3>Llamado laboral</h3>
                            <p>Llamado laboral de la Dirección Nacional de FUCVAM les ha efectuado un Trabajador Social para trabajar con la</p>
                            <div class="news-date">08 de enero del 2025</div>
                        </div>
                    </div>

                    <div class="news-item">
                        <div class="news-image">
                            <img src="IMG/result_20231227_05_1.jpg" alt="Congreso de Cooperativas" class="news-img">
                        </div>
                        <div class="news-content">
                            <h3>CONGRESO ANUAL DE COOPERATIVAS DE VIVIENDA</h3>
                            <p>CONVITES y la Federación a la Mesa "Quintañenas"</p>
                            <div class="news-date">15 de febrero del 2025</div>
                        </div>
                    </div>
                </div>

                <div class="news-column">
                    <div class="news-item">
                        <div class="news-image">
                            <img src="IMG/result_Proyecto Educativo.jpg" alt="Encuentro Nacional" class="news-img">
                        </div>
                        <div class="news-content">
                            <h3>ENCUENTRO NACIONAL HACIA UN FUTURO SOSTENIBLE</h3>
                            <p>Construyendo un presente, pasado y futuro rural para. Proyectos hacia para el desarrollo sostenible.</p>
                            <div class="news-date">También deberías saber algo con el desarrollo y el desarrollo económico desarrollo nacional breve como referencia a la fecha Hasta</div>
                        </div>
                    </div>

                    <div class="news-item">
                        <div class="news-image">
                            <img src="IMG/result_20250605-RVA-2334-2.jpg" alt="Obra Social" class="news-img">
                            <div class="news-overlay">INFORMES DE OBRA SOCIAL</div>
                        </div>
                        <div class="news-content">
                            <h3>Nuevo valor de cuota social</h3>
                            <p>A la fecha enero 2025</p>
                            <div class="news-date">18 de febrero del 2025</div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <div class="news-item">
                    <div class="news-image">
                        <img src="IMG/result_356d79_2790066112fd45c782e45cc5d95e6d88~mv2.jpg" alt="Proyecto Educativo" class="news-img">
                    </div>
                    <div class="news-content">
                        <h3>PROYECTO EDUCATIVO COMUNITARIO URUGUAY</h3>
                        <p>A-1-Sí Montevideo llevó a cabo el primer encuentro del proyecto "Cuadro de Mesa Sabiola"</p>
                        <p>Al-1-Sí Montevideo llevó a cabo el primer encuentro del proyecto educativo del Hábitat 2 de Convención, en el proyecto que...</p>
                        <div class="news-date">02 de febrero del 2025</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Proceso Section -->
    <section class="proceso-section">
        <div class="container">
            <h2 class="section-title">Proceso de Inscripción</h2>
            <p class="section-subtitle">Conoce los pasos para formar parte de una cooperativa de vivienda</p>
            
            <div class="proceso-grid">
                <div class="proceso-step">
                    <div class="paso-numero">1</div>
                    <h3>Información</h3>
                    <p>Infórmate sobre los requisitos y beneficios de pertenecer a una cooperativa de vivienda</p>
                </div>
                <div class="proceso-step">
                    <div class="paso-numero">2</div>
                    <h3>Inscripción</h3>
                    <p>Completa el formulario de inscripción y presenta la documentación requerida</p>
                </div>
                <div class="proceso-step">
                    <div class="paso-numero">3</div>
                    <h3>Evaluación</h3>
                    <p>Tu solicitud será evaluada por nuestro equipo técnico especializado</p>
                </div>
                <div class="proceso-step">
                    <div class="paso-numero">4</div>
                    <h3>Integración</h3>
                    <p>Una vez aprobada tu solicitud, formarás parte de la cooperativa</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonios Section -->
    <section id="testimonios" class="testimonios">
        <div class="container">
            <h2 class="testimonios-title">Lo que dicen nuestros cooperativistas</h2>
            
            <div class="testimonios-grid">
                <div class="testimonio">
                    <img src="IMG/descarga (1).png" alt="Martín Rodríguez" class="testimonio-avatar">
                    <p>"Gracias a Urban Coop pude acceder a mi vivienda propia. El proceso fue transparente y el acompañamiento excelente."</p>
                    <div class="stars">★★★★★</div>
                    <strong>Martín Rodríguez</strong>
                </div>
                <div class="testimonio">
                    <img src="IMG/wmremove-transformed.png" alt="Andrés Pereira" class="testimonio-avatar">
                    <p>"La experiencia cooperativa cambió mi vida. Ahora tengo mi hogar y formo parte de una comunidad solidaria."</p>
                    <div class="stars">★★★★</div>
                    <strong>Andrés Pereira</strong>
                </div>
                <div class="testimonio">
                    <img src="IMG/wmremove-transformed (2).png" alt="Lucía Fernández" class="testimonio-avatar">
                    <p>"Urban Coop me brindó la oportunidad de tener mi casa mediante el sistema cooperativo. Totalmente recomendable."</p>
                    <div class="stars">★★★★★</div>
                    <strong>Lucía Fernández</strong>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">150+</div>
                    <div class="stat-label">Cooperativas Activas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">2500+</div>
                    <div class="stat-label">Familias Beneficiadas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">25</div>
                    <div class="stat-label">Años de Experiencia</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Satisfacción</div>
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
                        <img src="IMG/UrbanCoop Black.jpeg" alt="" class="footer-logo-image">
                    </div>
                    <p>Facilitamos el acceso a la vivienda a través del sistema cooperativo, promoviendo la solidaridad y el desarrollo comunitario.</p>
                    <p>Tu hogar, nuestra misión.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Enlaces Rápidos</h3>
                    <a href="#cooperativas">Cooperativas</a>
                    <a href="#novedades">Novedades</a>
                    <a href="#proceso">Proceso</a>
                    <a href="#testimonios">Testimonios</a>
                    <a href="#contacto">Contacto</a>
                </div>
                
                <div class="footer-section">
                    <h3>Servicios</h3>
                    <a href="#">Cooperativas en Formación</a>
                    <a href="#">Cooperativas en Obra</a>
                    <a href="#">Cooperativas Habitadas</a>
                    <a href="#">Asesoramiento Legal</a>
                    <a href="#">Trabajo Social</a>
                </div>
                
                <div class="footer-section">
                    <h3>Contacto</h3>
                    <p>📍 Av. 18 de Julio 1234, Montevideo</p>
                    <p>📞 (+598) 2901-1234</p>
                    <p>✉️ info@urbancoop.com.uy</p>
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