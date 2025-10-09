<?php
// perfil.php - Versión frontend con redirección a FRONT/

session_start();

// Configuración de base de datos para validación básica
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

// FUNCIÓN DE VALIDACIÓN DE SESIÓN SIMPLIFICADA
function validateUserSession($pdo) {
    // Para acceso directo desde admin
    if (isset($_GET['user_id']) && isset($_GET['verify'])) {
        $user_id = intval($_GET['user_id']);
        $verify_token = $_GET['verify'];
        
        // Token simple para verificar que viene del admin
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
    
    // Para usuarios normales, la validación principal se hace en JavaScript
    return 'js_validation';
}

// VALIDAR AUTENTICACIÓN
$current_user = validateUserSession($pdo);

// Si la validación indica que debe manejarse por JavaScript, no bloqueamos aquí
if ($current_user === false) {
    header('Location: loginLP.php');
    exit();
}

// Variables por defecto (se actualizarán por JavaScript)
$user_name = 'Usuario';
$user_status = 2; // Por defecto aprobado
$user_id = 0;
$can_access = true;

// Si tenemos datos del usuario desde la URL (acceso desde admin)
if (is_array($current_user)) {
    $user_name = $current_user['usr_name'] . ' ' . $current_user['usr_surname'];
    $user_status = $current_user['estado'];
    $user_id = $current_user['id'];
    
    // Manejar diferentes estados del usuario
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
    <div id="loadingScreen" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #f5f5f5; display: flex; align-items: center; justify-content: center; z-index: 9999;">
        <div style="text-align: center;">
            <div style="width: 50px; height: 50px; border: 5px solid #d32f2f; border-top: 5px solid transparent; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
            <p>Cargando perfil...</p>
        </div>
    </div>

    <!-- Estado de espera o rechazo -->
    <div id="accessDeniedScreen" style="display: none; align-items: center; justify-content: center; height: 100vh; text-align: center;">
        <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <div id="statusIcon" style="font-size: 64px; margin-bottom: 20px;"></div>
            <h2 id="statusTitle"></h2>
            <p id="statusMessage" style="color: #666; margin-top: 10px;"></p>
            <button onclick="logout()" style="margin-top: 20px; padding: 10px 20px; background: #d32f2f; color: white; border: none; border-radius: 5px; cursor: pointer;">Volver al login</button>
        </div>
    </div>

    <!-- Main Application -->
    <div id="mainApp" style="display: none;">
        <div class="container">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <div class="logo">
                        <img src="IMG/UrbanCoop White.jpeg" alt="Urban Coop" class="logo-img">
                    </div>
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Buscar...">
                        <span class="menu-item-count" id="myDayCount">0</span>
                    </div>
                    
                    <div class="menu-item">
                        <span class="menu-item-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                            </svg>
                        </span>
                        <span class="menu-item-text">Importantes</span>
                        <span class="menu-item-count">0</span>
                    </div>
                    
                    <div class="menu-item">
                        <span class="menu-item-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11H5a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h4m6-6h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-4m-6-6V9a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </span>
                        <span class="menu-item-text">Tareas</span>
                        <span class="menu-item-count">0</span>
                    </div>
                    
                    <div class="menu-item">
                        <span class="menu-item-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9,22 9,12 15,12 15,22"></polyline>
                            </svg>
                        </span>
                        <span class="menu-item-text">Casa</span>
                        <span class="menu-item-count">3</span>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Header -->
                <div class="header">
                    <div class="header-left">
                        <nav class="header-nav">
                            <button class="nav-btn active" onclick="showSection('tasks')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 11H5a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h4m6-6h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-4m-6-6V9a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                                Tareas
                            </button>
                            <button class="nav-btn" onclick="showSection('payments')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14,2 14,8 20,8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10,9 9,9 8,9"></polyline>
                                </svg>
                                Comprobantes
                            </button>
                            <button class="nav-btn" onclick="showSection('hours')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12,6 12,12 16,14"></polyline>
                                </svg>
                                Horas Trabajadas
                            </button>
                        </nav>
                    </div>
                    
                    <div class="header-right">
                        <div class="profile-menu">
                            <button class="profile-btn" onclick="toggleProfileMenu()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span id="userNameDisplay">Usuario</span>
                            </button>
                            <div class="profile-dropdown" id="profileDropdown">
                                <a href="#" onclick="logout()">Cerrar Sesión</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Content Sections -->
                <div class="content-area">
                    <!-- Tasks Section -->
                    <div id="tasks-section" class="section active">
                        <div class="section-header">
                            <h2 class="section-title">Mis Tareas</h2>
                            <div class="task-actions">
                                <button class="add-btn" onclick="addNewTask()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    Agregar tarea
                                </button>
                            </div>
                        </div>
                        
                        <div class="task-list" id="taskList">
                            <div class="task-item" data-category="trabajo">
                                <input type="checkbox" class="task-checkbox" onchange="toggleTask(this)">
                                <span class="task-text">Completar registro de horas</span>
                                <div class="task-actions">
                                    <button class="star-btn" onclick="toggleFavorite(this)" title="Marcar como importante">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                                        </svg>
                                    </button>
                                    <button class="delete-btn" onclick="deleteTask(this)" title="Eliminar tarea">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3,6 5,6 21,6"></polyline>
                                            <path d="m19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="task-item" data-category="casa">
                                <input type="checkbox" class="task-checkbox" onchange="toggleTask(this)">
                                <span class="task-text">Actualizar datos personales</span>
                                <div class="task-actions">
                                    <button class="star-btn favorite" onclick="toggleFavorite(this)" title="Marcar como importante">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                                            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                                        </svg>
                                    </button>
                                    <button class="delete-btn" onclick="deleteTask(this)" title="Eliminar tarea">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3,6 5,6 21,6"></polyline>
                                            <path d="m19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="task-item" data-category="casa">
                                <input type="checkbox" class="task-checkbox" onchange="toggleTask(this)">
                                <span class="task-text">Programar reunión equipo</span>
                                <div class="task-actions">
                                    <button class="star-btn" onclick="toggleFavorite(this)" title="Marcar como importante">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                                        </svg>
                                    </button>
                                    <button class="delete-btn" onclick="deleteTask(this)" title="Eliminar tarea">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3,6 5,6 21,6"></polyline>
                                            <path d="m19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payments Section -->
                    <div id="payments-section" class="section">
                        <div class="section-header">
                            <h2 class="section-title">Comprobantes de Pago</h2>
                            <button class="add-btn" onclick="showUploadForm()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7,10 12,15 17,10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Subir Comprobante
                            </button>
                        </div>

                        <div id="paymentMessages"></div>

                        <div id="upload-form" style="display: none;">
                            <div class="form-container">
                                <form id="uploadPaymentForm" enctype="multipart/form-data" onsubmit="submitPaymentForm(event)">
                                    <input type="hidden" name="user_id" id="uploadUserId" value="">
                                    
                                    <div class="upload-area" id="uploadArea">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="7,10 12,15 17,10"></polyline>
                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                        </svg>
                                        <p>Arrastra y suelta tu archivo aquí o haz clic para seleccionar</p>
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
                                    
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" class="submit-btn">Subir Comprobante</button>
                                        <button type="button" class="submit-btn" onclick="hideUploadForm()" style="background: #666;">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="file-list" id="paymentsList">
                            <!-- Se carga dinámicamente con JavaScript -->
                        </div>
                    </div>

                    <!-- Hours Section -->
                    <div id="hours-section" class="section">
                        <div class="section-header">
                            <h2 class="section-title">Registro de Horas</h2>
                            <button class="add-btn" onclick="showHoursForm()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Registrar Horas
                            </button>
                        </div>

                        <!-- Resumen de horas del mes -->
                        <div class="hours-summary">
                            <h3>Resumen del mes actual</h3>
                            <p>Total de horas registradas: <strong id="totalHoursMonth">0 horas</strong></p>
                            <p>Mes: <span id="currentMonthDisplay"><?php echo date('F Y'); ?></span></p>
                        </div>

                        <div id="hours-form" style="display: none;">
                            <div class="form-container">
                                <div id="hoursMessages"></div>
                                
                                <form id="hoursForm" onsubmit="submitHoursForm(event)">
                                    <input type="hidden" name="user_id" id="hoursUserId" value="">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Fecha de Trabajo *</label>
                                            <input type="date" name="work_date" id="work_date" 
                                                   max="<?php echo date('Y-m-d'); ?>" 
                                                   required>
                                        </div>
                                        <div class="form-group">
                                            <label>Horas Trabajadas *</label>
                                            <input type="number" name="hours_worked" id="hours_worked" 
                                                   min="0.5" max="24" step="0.5" 
                                                   placeholder="8.0" 
                                                   required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Descripción del Trabajo *</label>
                                        <textarea name="description" id="description" rows="4" 
                                                  placeholder="Describe las actividades realizadas durante el día..." 
                                                  required></textarea>
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
                                    
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" class="submit-btn">Registrar Horas</button>
                                        <button type="button" class="submit-btn" onclick="hideHoursForm()" style="background: #666;">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="hours-list" id="hoursList">
                            <!-- Se carga dinámicamente con JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Button (only visible for admins) -->
        <button class="admin-btn" id="adminBtn" onclick="goToAdmin()" title="Panel de Administración" style="display: none;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 1l3 6 6 3-6 3-3 6-3-6-6-3 6-3z"></path>
            </svg>
        </button>

        <!-- Chat Button -->
        <button class="chat-btn" onclick="openChat()" title="Abrir chat">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </button>
    </div>

    <style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .admin-btn {
        position: fixed;
        bottom: 100px;
        right: 20px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
        border: none;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .admin-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(0,0,0,0.2);
    }

    .chat-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
        border: none;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .chat-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(0,0,0,0.2);
    }

    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }

    .alert-success {
        background-color: #e8f5e8;
        color: #2e7d32;
        border-left: 4px solid #4caf50;
    }

    .alert-error {
        background-color: #ffebee;
        color: #c62828;
        border-left: 4px solid #e53935;
    }
    </style>

    <script>
    // Variables globales
    let currentUser = null;
    let isUserDataLoaded = false;
const API_BASE = '../APIS/API_cooperativa.php';

    // Función principal de inicialización
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Iniciando carga del perfil...');
        
        // Verificar autenticación
        const userData = sessionStorage.getItem('user_data');
        
        if (!userData) {
            console.log('No hay datos de usuario en sessionStorage');
            redirectToLogin('No hay sesión activa');
            return;
        }

        try {
            currentUser = JSON.parse(userData);
            console.log('Usuario cargado:', currentUser);
            
            // Validar estructura de datos del usuario
            if (!currentUser.id || !currentUser.email || !currentUser.estado) {
                throw new Error('Datos de usuario incompletos');
            }

            // Verificar estado del usuario
            if (currentUser.estado == 1) {
                showAccessDenied('waiting', 'Cuenta en Espera', 'Esperando la aprobación manual de un administrador');
                return;
            } else if (currentUser.estado == 3) {
                showAccessDenied('rejected', 'Cuenta Rechazada', 'Usuario rechazado. Contacte con el administrador.');
                return;
            } else if (currentUser.estado != 2) {
                showAccessDenied('unknown', 'Estado Desconocido', 'Estado de usuario desconocido');
                return;
            }

            // Usuario aprobado - inicializar aplicación
            initializeApp();
            
        } catch (error) {
            console.error('Error al procesar datos de usuario:', error);
            sessionStorage.clear();
            redirectToLogin('Error en los datos de sesión');
        }
    });

    // Funciones de inicialización
    function showAccessDenied(type, title, message) {
        hideLoadingScreen();
        
        const screen = document.getElementById('accessDeniedScreen');
        const icon = document.getElementById('statusIcon');
        const titleEl = document.getElementById('statusTitle');
        const messageEl = document.getElementById('statusMessage');
        
        if (type === 'waiting') {
            icon.innerHTML = '⏳';
        } else if (type === 'rejected') {
            icon.innerHTML = '❌';
        } else {
            icon.innerHTML = '❓';
        }
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        screen.style.display = 'flex';
    }

    function initializeApp() {
        console.log('Inicializando aplicación para usuario:', currentUser.name);
        
        // Actualizar interfaz con datos del usuario
        updateUserInterface();
        
        // Cargar datos del usuario
        loadUserData();
        
        // Mostrar aplicación principal
        hideLoadingScreen();
        document.getElementById('mainApp').style.display = 'block';
        
        // Inicializar componentes
        updateTaskCount();
        setupFormHandlers();
        
        console.log('Aplicación inicializada correctamente');
        isUserDataLoaded = true;
    }

    function updateUserInterface() {
        const userNameDisplay = document.getElementById('userNameDisplay');
        if (userNameDisplay) {
            userNameDisplay.textContent = currentUser.name + ' ' + currentUser.surname;
        }
        
        // Mostrar botón de admin si es administrador
        if (currentUser.is_admin == 1) {
            const adminBtn = document.getElementById('adminBtn');
            if (adminBtn) {
                adminBtn.style.display = 'flex';
            }
        }
        
        // Actualizar campos ocultos con el ID del usuario
        const uploadUserId = document.getElementById('uploadUserId');
        const hoursUserId = document.getElementById('hoursUserId');
        
        if (uploadUserId) uploadUserId.value = currentUser.id;
        if (hoursUserId) hoursUserId.value = currentUser.id;
    }

    // Funciones API
    function loadUserData() {
        console.log('Cargando datos del usuario...');
        
        fetch(API_BASE + '?action=get_user_data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: currentUser.id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Datos cargados exitosamente');
                updatePaymentsList(data.payments);
                updateHoursList(data.hours);
                updateHoursMonth(data.total_hours_month, data.current_month);
            } else {
                console.error('Error al cargar datos:', data.message);
                showMessage('paymentMessages', 'Error al cargar datos: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error de conexión:', error);
            showMessage('paymentMessages', 'Error de conexión', 'error');
        });
    }

    function submitPaymentForm(event) {
        event.preventDefault();
        
        const formData = new FormData();
        const form = document.getElementById('uploadPaymentForm');
        
        // Agregar todos los campos del formulario
        formData.append('user_id', currentUser.id);
        formData.append('payment_month', form.payment_month.value);
        formData.append('payment_year', form.payment_year.value);
        formData.append('payment_description', form.payment_description.value);
        formData.append('payment_file', form.payment_file.files[0]);
        
        fetch(API_BASE + '?action=upload_payment', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('paymentMessages', data.message, 'success');
                hideUploadForm();
                form.reset();
                loadUserData(); // Recargar datos
            } else {
                showMessage('paymentMessages', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('paymentMessages', 'Error al subir el comprobante', 'error');
        });
    }

    function submitHoursForm(event) {
        event.preventDefault();
        
        const form = document.getElementById('hoursForm');
        
        const hoursData = {
            user_id: currentUser.id,
            work_date: form.work_date.value,
            hours_worked: parseFloat(form.hours_worked.value),
            description: form.description.value,
            work_type: form.work_type.value
        };
        
        fetch(API_BASE + '?action=register_hours', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(hoursData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('hoursMessages', data.message, 'success');
                hideHoursForm();
                form.reset();
                loadUserData(); // Recargar datos
            } else {
                showMessage('hoursMessages', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('hoursMessages', 'Error al registrar las horas', 'error');
        });
    }

    function deletePayment(paymentId) {
        if (!confirm('¿Estás seguro de que quieres eliminar este comprobante?')) {
            return;
        }
        
        fetch(API_BASE + '?action=delete_payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                payment_id: paymentId,
                user_id: currentUser.id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('paymentMessages', data.message, 'success');
                loadUserData(); // Recargar datos
            } else {
                showMessage('paymentMessages', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('paymentMessages', 'Error al eliminar el comprobante', 'error');
        });
    }

    function deleteHours(hoursId) {
        if (!confirm('¿Estás seguro de que quieres eliminar este registro?')) {
            return;
        }
        
        fetch(API_BASE + '?action=delete_hours', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                hours_id: hoursId,
                user_id: currentUser.id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('hoursMessages', data.message, 'success');
                loadUserData(); // Recargar datos
            } else {
                showMessage('hoursMessages', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('hoursMessages', 'Error al eliminar el registro', 'error');
        });
    }

    // Funciones de UI
    function updatePaymentsList(payments) {
        const paymentsList = document.getElementById('paymentsList');
        
        if (!payments || payments.length === 0) {
            paymentsList.innerHTML = `
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14,2 14,8 20,8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10,9 9,9 8,9"></polyline>
                    </svg>
                    <h3>No hay comprobantes de pago</h3>
                    <p>Sube tu primer comprobante de pago para comenzar.</p>
                </div>
            `;
            return;
        }
        
        let paymentsHTML = '';
        payments.forEach(payment => {
            const fileIcon = payment.file_type.includes('pdf') ? 
                `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d32f2f" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14,2 14,8 20,8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10,9 9,9 8,9"></polyline>
                </svg>` : 
                `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21,15 16,10 5,21"></polyline>
                </svg>`;
            
            paymentsHTML += `
                <div class="file-item">
                    <div class="file-info-wrapper">
                        <div class="file-icon">${fileIcon}</div>
                        <div class="file-info">
                            <h3>Comprobante ${payment.month_name} ${payment.year}</h3>
                            <p>Subido el ${payment.created_at} • ${payment.file_size}</p>
                            ${payment.description ? `<p style="font-style: italic; margin-top: 5px;">${payment.description}</p>` : ''}
                        </div>
                    </div>
                    <div class="file-actions">
                        <span class="file-status status-${payment.status}">${payment.status.charAt(0).toUpperCase() + payment.status.slice(1)}</span>
                        <a href="${API_BASE}?action=download_payment&id=${payment.id}&user_id=${currentUser.id}" class="action-btn" title="Descargar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7,10 12,15 17,10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Descargar
                        </a>
                        <button onclick="deletePayment(${payment.id})" class="delete-payment-btn">Eliminar</button>
                    </div>
                </div>
            `;
        });
        
        paymentsList.innerHTML = paymentsHTML;
    }

    function updateHoursList(hours) {
        const hoursList = document.getElementById('hoursList');
        
        if (!hours || hours.length === 0) {
            hoursList.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #666;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 20px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12,6 12,12 16,14"></polyline>
                    </svg>
                    <h3>No hay registros de horas</h3>
                    <p>Comienza registrando tus primeras horas de trabajo.</p>
                </div>
            `;
            return;
        }
        
        let hoursHTML = '';
        hours.forEach(record => {
            hoursHTML += `
                <div class="hours-item">
                    <div class="hours-actions">
                        <button onclick="deleteHours(${record.id})" class="delete-hours-btn">Eliminar</button>
                    </div>
                    <div class="hours-info">
                        <h3>${record.work_date_formatted} - ${record.hours_worked} horas</h3>
                        <p><strong>Tipo:</strong> ${record.work_type}</p>
                        <p>${record.description}</p>
                        <small style="color: #666;">Registrado el ${record.created_at}</small>
                    </div>
                </div>
            `;
        });
        
        hoursList.innerHTML = hoursHTML;
    }

    function updateHoursMonth(totalHours, currentMonth) {
        const totalHoursMonth = document.getElementById('totalHoursMonth');
        const currentMonthDisplay = document.getElementById('currentMonthDisplay');
        
        if (totalHoursMonth) {
            totalHoursMonth.textContent = `${totalHours} horas`;
        }
        
        if (currentMonthDisplay) {
            currentMonthDisplay.textContent = currentMonth;
        }
    }

    function showMessage(containerId, message, type) {
        const container = document.getElementById(containerId);
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        
        container.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
    }

    function hideLoadingScreen() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            loadingScreen.style.display = 'none';
        }
    }

    function redirectToLogin(message) {
        console.log('Redirigiendo al login:', message);
        hideLoadingScreen();
        
        if (message) {
            alert(message);
        }
        
        sessionStorage.clear();
        window.location.href = 'loginLP.php';
    }

    // Funciones de navegación
    function showSection(sectionName) {
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');
        });
        
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.getElementById(sectionName + '-section').classList.add('active');
        event.target.closest('.nav-btn').classList.add('active');
    }

    function toggleProfileMenu() {
        const dropdown = document.getElementById('profileDropdown');
        dropdown.classList.toggle('show');
    }

    // Cerrar dropdown cuando se hace clic fuera
    document.addEventListener('click', function(event) {
        const profileMenu = document.querySelector('.profile-menu');
        if (!profileMenu.contains(event.target)) {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) {
                dropdown.classList.remove('show');
            }
        }
    });

    // Funciones de tareas
    function toggleTask(checkbox) {
        const taskItem = checkbox.closest('.task-item');
        if (checkbox.checked) {
            taskItem.classList.add('completed');
        } else {
            taskItem.classList.remove('completed');
        }
        updateTaskCount();
    }

    function addNewTask() {
        const taskText = prompt('Ingresa el texto de la nueva tarea:');
        if (taskText && taskText.trim()) {
            const category = prompt('Selecciona la categoría (trabajo/casa):') || 'trabajo';
            
            const taskList = document.getElementById('taskList');
            const newTask = document.createElement('div');
            newTask.className = 'task-item';
            newTask.setAttribute('data-category', category);
            newTask.innerHTML = `
                <input type="checkbox" class="task-checkbox" onchange="toggleTask(this)">
                <span class="task-text">${taskText.trim()}</span>
                <div class="task-actions">
                    <button class="star-btn" onclick="toggleFavorite(this)" title="Marcar como importante">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                        </svg>
                    </button>
                    <button class="delete-btn" onclick="deleteTask(this)" title="Eliminar tarea">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3,6 5,6 21,6"></polyline>
                            <path d="m19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"></path>
                        </svg>
                    </button>
                </div>
            `;
            taskList.appendChild(newTask);
            updateTaskCount();
        }
    }

    function toggleFavorite(button) {
        const taskItem = button.closest('.task-item');
        const svg = button.querySelector('svg');
        
        if (button.classList.contains('favorite')) {
            button.classList.remove('favorite');
            svg.setAttribute('fill', 'none');
        } else {
            button.classList.add('favorite');
            svg.setAttribute('fill', 'currentColor');
        }
        updateTaskCount();
    }

    function deleteTask(button) {
        if (confirm('¿Estás seguro de que quieres eliminar esta tarea?')) {
            const taskItem = button.closest('.task-item');
            taskItem.remove();
            updateTaskCount();
        }
    }

    function updateTaskCount() {
        const totalTasks = document.querySelectorAll('.task-item').length;
        const completedTasks = document.querySelectorAll('.task-item.completed').length;
        const remainingTasks = totalTasks - completedTasks;
        const favoriteTasks = document.querySelectorAll('.star-btn.favorite').length;
        const casaTasks = document.querySelectorAll('.task-item[data-category="casa"]').length;
        
        const myDayCount = document.getElementById('myDayCount');
        if (myDayCount) myDayCount.textContent = remainingTasks;
        
        const menuItems = document.querySelectorAll('.menu-item .menu-item-count');
        if (menuItems[1]) menuItems[1].textContent = favoriteTasks;
        if (menuItems[2]) menuItems[2].textContent = totalTasks;
        if (menuItems[3]) menuItems[3].textContent = casaTasks;
    }

    // Funciones de formularios
    function showUploadForm() {
        document.getElementById('upload-form').style.display = 'block';
    }

    function hideUploadForm() {
        document.getElementById('upload-form').style.display = 'none';
    }

    function showHoursForm() {
        document.getElementById('hours-form').style.display = 'block';
        document.getElementById('hours-form').scrollIntoView({ behavior: 'smooth' });
    }

    function hideHoursForm() {
        document.getElementById('hours-form').style.display = 'none';
        document.getElementById('hoursForm').reset();
    }
    
    function setupFormHandlers() {
        // Configurar drag & drop para archivos
        const uploadArea = document.getElementById('uploadArea');
        if (uploadArea) {
            uploadArea.addEventListener('click', function() {
                this.querySelector('input[type="file"]').click();
            });

            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = '#d32f2f';
                this.style.backgroundColor = '#fafafa';
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = '#ddd';
                this.style.backgroundColor = 'white';
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = '#ddd';
                this.style.backgroundColor = 'white';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    this.querySelector('input[type="file"]').files = files;
                    this.querySelector('p').textContent = files[0].name;
                }
            });
        }
    }

    // Funciones de utilidad
    function logout() {
        if (confirm('¿Estás seguro que quieres cerrar sesión?')) {
            sessionStorage.clear();
            alert('Sesión cerrada exitosamente');
            window.location.href = 'loginLP.php';
        }
    }

    function goToAdmin() {
        if (currentUser && currentUser.is_admin == 1) {
            window.location.href = 'admin.php';
        } else {
            alert('No tienes permisos para acceder al panel de administración');
        }
    }

    function openChat() {
        alert('Función de chat en desarrollo');
    }

    // Inicializar componentes después de cargar la app
    setTimeout(() => {
        if (isUserDataLoaded) {
            setupFormHandlers();
        }
    }, 1000);
    </script>
</body>
</html>