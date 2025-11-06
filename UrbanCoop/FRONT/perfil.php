<?php
// perfil.php - Versión mejorada con dashboard y pago inicial

session_start();

// Configuración de base de datos
$host = 'localhost';
$dbname = 'usuarios_urban_coop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// FUNCIÓN DE VALIDACIÓN DE SESIÓN
function validateUserSession($pdo) {
    if (isset($_GET['user_id']) && isset($_GET['verify'])) {
        $user_id = intval($_GET['user_id']);
        $verify_token = $_GET['verify'];
        
        $expected_token = md5('admin_access_' . $user_id . date('Y-m-d'));
        
        if ($verify_token === $expected_token) {
            try {
                $stmt = $pdo->prepare("SELECT id, usr_name, usr_surname, estado, usr_email, is_admin FROM usuario WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    return $user;
                }
            } catch(PDOException $e) {
                return false;
            }
        }
    }
    
    return 'js_validation';
}

// VALIDAR AUTENTICACIÓN
$current_user = validateUserSession($pdo);

if ($current_user === false) {
    header('Location: loginLP.php');
    exit();
}

$user_name = 'Usuario';
$user_status = 2;
$user_id = 0;
$can_access = true;

if (is_array($current_user)) {
    $user_name = $current_user['usr_name'] . ' ' . $current_user['usr_surname'];
    $user_status = $current_user['estado'];
    $user_id = $current_user['id'];
    
    $status_message = '';
    
    switch ($user_status) {
        case 1:
            $status_message = 'Esperando la aprobación manual de un administrador';
            $can_access = false;
            break;
        case 2:
            $can_access = true;
            break;
        case 3:
            $status_message = 'Usuario rechazado. Contacte con el administrador.';
            $can_access = false;
            break;
        default:
            $status_message = 'Estado de usuario desconocido';
            $can_access = false;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urban Coop - Dashboard</title>
    <link rel="stylesheet" href="CSS/perfilStyles.css">
</head>
<body>
    <!-- Loading Screen -->
    <div id="loadingScreen" class="loading-screen">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Cargando perfil...</p>
        </div>
    </div>

    <!-- Modal de Pago Inicial MEJORADO -->
    <div id="initialPaymentModal" class="modal-overlay" style="display: none;">
        <div class="modal">
            <button class="modal-close-btn" onclick="closeInitialPaymentModal()" title="Cerrar">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="modal-header">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ff9800" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div>
                    <h2>¡Bienvenido a Urban Coop!</h2>
                    <p style="color: #666; font-size: 14px; margin-top: 4px;">Pago Inicial Requerido</p>
                </div>
            </div>
            <div class="modal-content">
                <div class="alert alert-info" style="margin-bottom: 20px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <div>Para activar tu cuenta y acceder a todas las funcionalidades, debes realizar el pago inicial de ingreso a la cooperativa.</div>
                </div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 500; color: #666;">Monto requerido:</span>
                        <span style="font-size: 28px; font-weight: 700; color: #d32f2f;">$50,000</span>
                    </div>
                </div>
                <p style="font-size: 14px; color: #666; line-height: 1.6;">
                    Una vez aprobado tu pago, se te asignará una unidad habitacional y podrás comenzar a utilizar todas las funciones del sistema:
                </p>
                <ul style="margin: 12px 0; padding-left: 20px; color: #666; font-size: 14px;">
                    <li style="margin: 8px 0;">✓ Registrar horas trabajadas</li>
                    <li style="margin: 8px 0;">✓ Gestionar pagos mensuales</li>
                    <li style="margin: 8px 0;">✓ Acceso a tu unidad habitacional</li>
                    <li style="margin: 8px 0;">✓ Participar en reuniones y eventos</li>
                </ul>
            </div>
            <div class="modal-actions">
                <button class="btn btn-primary btn-large" onclick="handleInitialPayment()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Subir Comprobante de Pago Inicial
                </button>
                <button class="btn btn-secondary" onclick="closeInitialPaymentModal()">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Estado de espera o rechazo -->
    <div id="accessDeniedScreen" style="display: none; align-items: center; justify-content: center; height: 100vh; text-align: center;">
        <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <div id="statusIcon" style="font-size: 64px; margin-bottom: 20px;"></div>
            <h2 id="statusTitle"></h2>
            <p id="statusMessage" style="color: #666; margin-top: 10px;"></p>
            <button onclick="logout()" class="btn btn-secondary">Volver al login</button>
        </div>
    </div>

    <!-- Main Application -->
    <div id="mainApp" style="display: none;">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="logo-section">
                    <img src="IMG/UrbanCoop White.jpeg" alt="Urban Coop" class="logo-img">
                    <h1>Urban Coop</h1>
                </div>
                <div class="header-right">
                    <div class="profile-dropdown">
                        <button class="profile-button" id="profileButton">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span id="userNameDisplay">Usuario</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="profile-menu" id="profileDropdown">
                            <a href="#" onclick="logout()">Cerrar Sesión</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="main-layout">
            <!-- Sidebar -->
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <button class="nav-button active" data-section="dashboard">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        </svg>
                        <span>Info</span>
                    </button>

                    <div class="nav-group">
                        <button class="nav-button nav-toggle" data-menu="payments">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="16"></line>
                                <line x1="8" y1="12" x2="16" y2="12"></line>
                            </svg>
                            <span>Pagos</span>
                            <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        <div class="nav-submenu" id="payments-submenu">
                            <button class="nav-subitem" data-section="payments">Comprobantes</button>
                            <button class="nav-subitem" data-section="remuneration">Remuneración de Horas</button>
                        </div>
                    </div>

                    <div class="nav-group">
                        <button class="nav-button nav-toggle" data-menu="hours">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span>Horas</span>
                            <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        <div class="nav-submenu" id="hours-submenu">
                            <button class="nav-subitem" data-section="hours">Horas Trabajadas</button>
                            <button class="nav-subitem" data-section="meetings">Asistencia a Reuniones</button>
                        </div>
                    </div>

                    <button class="nav-button" data-section="unit">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                            <path d="M3 9h18"></path>
                            <path d="M9 21V9"></path>
                        </svg>
                        <span>Mi Unidad</span>
                    </button>

                    <button class="nav-button" data-section="tasks">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"></path>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                        <span>Tareas</span>
                    </button>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Dashboard Section -->
                <div id="dashboard-section" class="content-section active">
                    <h1 class="page-title">Dashboard</h1>
                    
                    <!-- Banner de advertencia MEJORADO con botón de cierre -->
                    <div id="noAccessWarning" class="alert alert-warning banner-closeable" style="display: none;">
                        <button class="banner-close-btn" onclick="closeBanner()" title="Cerrar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <div style="flex: 1;">
                            <strong>⚠️ Acción requerida: Pago Inicial Pendiente</strong>
                            <p style="margin: 8px 0 0 0; font-size: 14px;">
                                Debes realizar el pago inicial de <strong>$50,000</strong> para acceder a todas las funcionalidades del sistema.
                            </p>
                        </div>
                    </div>

                    <!-- Botón destacado para pago inicial -->
                    <div id="initialPaymentAction" class="initial-payment-cta" style="display: none;">
                        <div class="cta-content">
                            <div class="cta-icon">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="16"></line>
                                    <line x1="8" y1="12" x2="16" y2="12"></line>
                                </svg>
                            </div>
                            <div class="cta-text">
                                <h3>¡Completa tu Pago Inicial!</h3>
                                <p>Realiza el pago de ingreso de <strong>$50,000</strong> para activar tu cuenta y acceder a todos los servicios.</p>
                            </div>
                            <button class="btn btn-primary btn-large" onclick="handleInitialPayment()">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Subir Comprobante Ahora
                            </button>
                        </div>
                    </div>

                    <div class="dashboard-grid">
                        <!-- Estado Financiero -->
                        <div class="info-card">
                            <div class="card-header">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="card-icon">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                <h2>Estado Financiero</h2>
                            </div>
                            <div class="card-content">
                                <div class="stat-row">
                                    <span class="stat-label">Cuota mensual:</span>
                                    <span class="stat-value" id="monthlyFee">$22,000</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Total pagado:</span>
                                    <span class="stat-value" id="totalPaid">$15,000</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Aprobado:</span>
                                    <span class="stat-value stat-approved" id="approvedAmount">$10,000</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Pendiente:</span>
                                    <span class="stat-value stat-pending" id="pendingAmount">$5,000</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" id="financialProgress"></div>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Faltante:</span>
                                    <span class="stat-value stat-negative" id="remainingAmount">$12,000</span>
                                </div>
                            </div>
                        </div>

                        <!-- Horas del Mes -->
                        <div class="info-card">
                            <div class="card-header">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="card-icon">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <h2>Horas del Mes</h2>
                            </div>
                            <div class="card-content">
                                <div class="stat-row">
                                    <span class="stat-label">Horas registradas:</span>
                                    <span class="stat-value" id="registeredHours">12 hrs</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Horas requeridas:</span>
                                    <span class="stat-value" id="requiredHours">20 hrs</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" id="hoursProgress"></div>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Faltantes:</span>
                                    <span class="stat-value stat-negative" id="missingHours">8 hrs</span>
                                </div>
                            </div>
                        </div>

                        <!-- Asistencia a Reuniones -->
                        <div class="info-card">
                            <div class="card-header">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="card-icon">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <h2>Asistencia a Reuniones</h2>
                            </div>
                            <div class="card-content">
                                <div class="stat-row">
                                    <span class="stat-label">Asistencias:</span>
                                    <span class="stat-value" id="meetingsAttended">1 / 3</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" id="meetingsProgress"></div>
                                </div>
                                <div class="alert alert-info" id="meetingsAlert">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    <small>Debes asistir a mínimo 3 reuniones por mes</small>
                                </div>
                                
                                <div class="upcoming-meetings" id="upcomingMeetings">
                                    <h4>Próximas Reuniones</h4>
                                    <!-- Se llena dinámicamente -->
                                </div>
                            </div>
                        </div>

                        <!-- Mi Unidad -->
                        <div class="info-card">
                            <div class="card-header">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="card-icon">
                                    <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                    <path d="M3 9h18"></path>
                                    <path d="M9 21V9"></path>
                                </svg>
                                <h2>Mi Unidad</h2>
                            </div>
                            <div class="card-content" id="unitInfo">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagos Section -->
                <div id="payments-section" class="content-section">
                    <h1 class="page-title">Comprobantes de Pago</h1>
                    <button class="btn btn-primary" id="uploadPaymentBtn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        Subir Comprobante
                    </button>

                    <div id="paymentMessages"></div>

                    <div id="upload-form" style="display: none;">
                        <div class="form-container">
                            <form id="uploadPaymentForm" enctype="multipart/form-data">
                                <input type="hidden" name="user_id" id="uploadUserId">
                                
                                <div class="upload-area" id="uploadArea">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <p class="upload-text">Arrastra y suelta tu archivo aquí</p>
                                    <p style="font-size: 12px; color: #666; margin-top: 5px;">PDF, JPG, PNG - Máximo 5MB</p>
                                    <input type="file" name="payment_file" id="payment_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Mes de Pago *</label>
                                        <select name="payment_month" id="payment_month" required>
                                            <option value="">Seleccionar mes</option>
                                            <option value="01">Enero</option>
                                            <option value="02">Febrero</option>
                                            <option value="03">Marzo</option>
                                            <option value="04">Abril</option>
                                            <option value="05">Mayo</option>
                                            <option value="06">Junio</option>
                                            <option value="07">Julio</option>
                                            <option value="08">Agosto</option>
                                            <option value="09">Septiembre</option>
                                            <option value="10">Octubre</option>
                                            <option value="11">Noviembre</option>
                                            <option value="12">Diciembre</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Año *</label>
                                        <select name="payment_year" id="payment_year" required>
                                            <option value="">Seleccionar año</option>
                                            <option value="2024">2024</option>
                                            <option value="2025">2025</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Descripción (opcional)</label>
                                    <textarea name="payment_description" id="payment_description" rows="3" placeholder="Agregar notas adicionales..."></textarea>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">Subir Comprobante</button>
                                    <button type="button" class="btn btn-secondary" id="cancelUpload">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="file-list" id="paymentsList"></div>
                </div>

                <!-- Remuneración Section -->
                <div id="remuneration-section" class="content-section">
                    <h1 class="page-title">Remuneración de Horas</h1>
                    <div class="content-placeholder">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.3">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <p>Información sobre la remuneración de tus horas trabajadas</p>
                    </div>
                </div>

                <!-- Horas Section -->
                <div id="hours-section" class="content-section">
                    <h1 class="page-title">Horas Trabajadas</h1>
                    <button class="btn btn-primary" id="addHoursBtn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Registrar Horas
                    </button>

                    <div id="hoursMessages"></div>

                    <div id="hours-form" style="display: none;">
                        <div class="form-container">
                            <form id="hoursForm">
                                <input type="hidden" name="user_id" id="hoursUserId">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Fecha de Trabajo *</label>
                                        <input type="date" name="work_date" id="work_date" max="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Horas Trabajadas *</label>
                                        <input type="number" name="hours_worked" id="hours_worked" min="0.5" max="24" step="0.5" placeholder="8.0" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Descripción del Trabajo *</label>
                                    <textarea name="description" id="description" rows="4" placeholder="Describe las actividades realizadas..." required></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Tipo de Trabajo *</label>
                                    <select name="work_type" id="work_type" required>
                                        <option value="">Seleccionar tipo</option>
                                        <option value="desarrollo">Desarrollo</option>
                                        <option value="reunion">Reuniones</option>
                                        <option value="documentacion">Documentación</option>
                                        <option value="testing">Testing</option>
                                        <option value="administrativo">Administrativo</option>
                                        <option value="soporte">Soporte Técnico</option>
                                        <option value="investigacion">Investigación</option>
                                        <option value="otros">Otros</option>
                                    </select>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">Registrar Horas</button>
                                    <button type="button" class="btn btn-secondary" id="cancelHours">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="hours-list" id="hoursList"></div>
                </div>

                <!-- Meetings Section -->
                <div id="meetings-section" class="content-section">
                    <h1 class="page-title">Asistencia a Reuniones</h1>
                    <div class="alert alert-info">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <div>Debes asistir a mínimo 3 reuniones por mes.</div>
                    </div>
                    <div class="meetings-list" id="meetingsList"></div>
                </div>

                <!-- Unit Section -->
                <div id="unit-section" class="content-section">
                    <h1 class="page-title">Mi Unidad</h1>
                    <div class="content-placeholder" id="unitDetails">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.3">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                            <path d="M3 9h18"></path>
                            <path d="M9 21V9"></path>
                        </svg>
                        <p>Información sobre tu unidad habitacional</p>
                    </div>
                </div>

                <!-- Tasks Section -->
                <div id="tasks-section" class="content-section">
                    <h1 class="page-title">Mis Tareas</h1>
                    <button class="btn btn-primary" id="addTaskBtn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Agregar Tarea
                    </button>
                    <div class="task-list" id="taskList"></div>
                </div>
            </main>
        </div>

        <!-- Admin Button -->
        <button class="floating-btn admin-btn" id="adminBtn" onclick="goToAdmin()" title="Panel de Administración" style="display: none;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 1a3 3 0 1 0 0 6 3 3 0 1 0 0-6"></path>
                <path d="M12 8v13"></path>
            </svg>
        </button>

        <!-- Chat Button -->
        <button class="floating-btn chat-btn" onclick="openChat()" title="Abrir chat">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </button>
    </div>

    <script src="../FRONT/JSS/perfil.js"></script>
</body>
</html>