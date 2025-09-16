<?php
// perfil.php - Sistema de Pagos Mejorado con Saldo
session_start();

// Configuración de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// FUNCIÓN DE VALIDACIÓN SIMPLIFICADA
function validateUserAccess() {
    return true;
}

// Variables por defecto
$user_name = 'Usuario';
$user_status = 2;
$user_id = 0;
$can_access = true;

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
                        </div>
                    </div>

                    <!-- Payments Section -->
                    <div id="payments-section" class="section">
                        <div class="section-header">
                            <h2 class="section-title">Comprobantes de Pago</h2>
                            <div class="payment-actions">
                                <button class="add-btn" onclick="showUploadForm()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7,10 12,15 17,10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    Subir Comprobante
                                </button>
                            </div>
                        </div>

                        <!-- Payment Summary Cards -->
                        <div class="payment-summary-cards">
                            <div class="summary-card balance-card">
                                <div class="card-header">
                                    <h3>Saldo Actual</h3>
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                        <line x1="1" y1="10" x2="23" y2="10"></line>
                                    </svg>
                                </div>
                                <div class="card-amount" id="currentBalance">$0</div>
                                <div class="card-status" id="paymentStatus">Cargando...</div>
                            </div>

                            <div class="summary-card fee-card">
                                <div class="card-header">
                                    <h3>Cuota Mensual</h3>
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                </div>
                                <div class="card-amount" id="monthlyFee">$22.000</div>
                                <div class="card-subtitle">Cuota fija mensual</div>
                            </div>

                            <div class="summary-card progress-card">
                                <div class="card-header">
                                    <h3>Progreso de Pago</h3>
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                    </svg>
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar">
                                        <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                                    </div>
                                    <div class="progress-text" id="progressText">0%</div>
                                </div>
                                <div class="card-subtitle">Completado este mes</div>
                            </div>
                        </div>

                        <div id="paymentMessages"></div>

                        <!-- Upload Form -->
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
                                        <label>Importe del Pago *</label>
                                        <div class="amount-input-container">
                                            <span class="currency-symbol">$</span>
                                            <input type="number" name="payment_amount" id="payment_amount" 
                                                   min="1000" max="1000000" step="1" 
                                                   placeholder="22000" 
                                                   required>
                                        </div>
                                        <small style="color: #666;">Ingrese el monto sin puntos ni comas</small>
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

                        <!-- Balance Form -->
                        <div id="balance-form" style="display: none;">
                            <div class="form-container">
                                <form id="balanceForm" onsubmit="submitBalanceForm(event)">
                                    <h3 style="margin-bottom: 20px; color: #d32f2f;">Agregar Saldo a la Cuenta</h3>
                                    <div class="form-group">
                                        <label>Monto a Agregar *</label>
                                        <div class="amount-input-container">
                                            <span class="currency-symbol">$</span>
                                            <input type="number" name="balance_amount" id="balance_amount" 
                                                   min="100" max="500000" step="1" 
                                                   placeholder="22000" 
                                                   required>
                                        </div>
                                        <small style="color: #666;">Monto entre $100 y $500.000</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Descripción (opcional)</label>
                                        <textarea name="balance_description" id="balance_description" rows="2" placeholder="Motivo del ingreso de saldo..."></textarea>
                                    </div>
                                    
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" class="submit-btn">Agregar Saldo</button>
                                        <button type="button" class="submit-btn" onclick="hideBalanceForm()" style="background: #666;">Cancelar</button>
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

    /* Payment Summary Cards */
    .payment-summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-left: 4px solid #d32f2f;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }

    .summary-card .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .summary-card .card-header h3 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .summary-card .card-header svg {
        color: #d32f2f;
        opacity: 0.7;
    }

    .summary-card .card-amount {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin-bottom: 8px;
        line-height: 1;
    }

    .balance-card .card-amount {
        color: #2e7d32;
    }

    .fee-card .card-amount {
        color: #d32f2f;
    }

    .summary-card .card-status,
    .summary-card .card-subtitle {
        font-size: 14px;
        color: #666;
        margin: 0;
    }

    .progress-card .progress-container {
        margin-bottom: 12px;
    }

    .progress-bar {
        width: 100%;
        height: 12px;
        background: #f0f0f0;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 8px;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #4caf50 0%, #2e7d32 100%);
        border-radius: 6px;
        transition: width 0.6s ease;
        position: relative;
    }

    .progress-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.2) 50%, transparent 100%);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .progress-text {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        text-align: center;
    }

    /* Payment Actions */
    .payment-actions {
        display: flex;
        gap: 10px;
    }

    .secondary-btn {
        background: #2196f3 !important;
    }

    .secondary-btn:hover {
        background: #1976d2 !important;
    }

    /* Amount Input Container */
    .amount-input-container {
        position: relative;
        display: flex;
        align-items: center;
    }

    .currency-symbol {
        position: absolute;
        left: 15px;
        font-size: 18px;
        font-weight: 600;
        color: #666;
        z-index: 1;
    }

    .amount-input-container input {
        padding-left: 35px !important;
        font-size: 16px;
        font-weight: 600;
    }

    /* Enhanced form styles */
    .form-container {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-bottom: 24px;
        border-left: 4px solid #d32f2f;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #d32f2f;
        box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1);
    }

    .upload-area {
        border: 2px dashed #d0d0d0;
        border-radius: 12px;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }

    .upload-area:hover {
        border-color: #d32f2f;
        background: #fafafa;
    }

    .upload-area input[type="file"] {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    .upload-area p {
        margin: 10px 0 0 0;
        color: #666;
    }

    .upload-area svg {
        color: #d32f2f;
        margin-bottom: 16px;
    }

    .submit-btn {
        padding: 12px 24px;
        background: #d32f2f;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .submit-btn:hover {
        background: #b71c1c;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(211, 47, 47, 0.3);
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

    /* Responsive design */
    @media (max-width: 768px) {
        .payment-summary-cards {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .payment-actions {
            flex-direction: column;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .summary-card {
            padding: 20px;
        }

        .summary-card .card-amount {
            font-size: 28px;
        }
    }
    </style>

    <script>
    // Variables globales
    let currentUser = null;
    let isUserDataLoaded = false;

    // CONFIGURACIÓN DE API
    const API_BASE = './API_cooperativa.php';

    // FUNCIÓN DE VALIDACIÓN DE SESIÓN SIMPLIFICADA
    function isSessionValid() {
        const userData = localStorage.getItem('user_data');
        const loginTime = localStorage.getItem('login_time');
        
        if (!userData || !loginTime) {
            return false;
        }
        
        try {
            const user = JSON.parse(userData);
            const now = new Date().getTime();
            const login = parseInt(loginTime);
            
            if (!user.id || !user.email) {
                return false;
            }
            
            // Verificar que no hayan pasado más de 24 horas
            const SESSION_TIMEOUT = 24 * 60 * 60 * 1000;
            if (now - login > SESSION_TIMEOUT) {
                localStorage.removeItem('user_data');
                localStorage.removeItem('login_time');
                return false;
            }
            
            return true;
        } catch (e) {
            localStorage.removeItem('user_data');
            localStorage.removeItem('login_time');
            return false;
        }
    }

    function getCurrentUser() {
        if (!isSessionValid()) {
            return null;
        }
        
        try {
            const userData = localStorage.getItem('user_data');
            return JSON.parse(userData);
        } catch (e) {
            localStorage.removeItem('user_data');
            localStorage.removeItem('login_time');
            return null;
        }
    }

    function clearSession() {
        localStorage.removeItem('user_data');
        localStorage.removeItem('login_time');
    }

    // FUNCIÓN PRINCIPAL DE INICIALIZACIÓN
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== PERFIL INITIALIZATION ===');
        
        // Verificar si hay sesión válida
        if (!isSessionValid()) {
            redirectToLogin('No hay sesión activa');
            return;
        }

        // Obtener usuario de localStorage
        currentUser = getCurrentUser();
        
        if (!currentUser) {
            redirectToLogin('Error en los datos de sesión');
            return;
        }
        
        // Validar estructura de datos del usuario
        if (!currentUser.id || !currentUser.email || currentUser.estado === undefined) {
            clearSession();
            redirectToLogin('Datos de usuario incompletos');
            return;
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
    });

    // Funciones de inicialización
    function showAccessDenied(type, title, message) {
        hideLoadingScreen();
        
        const screen = document.getElementById('accessDeniedScreen');
        const icon = document.getElementById('statusIcon');
        const titleEl = document.getElementById('statusTitle');
        const messageEl = document.getElementById('statusMessage');
        
        if (type === 'waiting') {
            icon.innerHTML = '⳿';
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
        console.log('Inicializando aplicación para usuario:', currentUser.name || currentUser.usr_name);
        
        // Actualizar interfaz con datos del usuario
        updateUserInterface();
        
        // Cargar datos del usuario
        loadUserData();
        
        // Mostrar aplicación principal
        hideLoadingScreen();
        document.getElementById('mainApp').style.display = 'block';
        
        isUserDataLoaded = true;
    }

    function updateUserInterface() {
        const userNameDisplay = document.getElementById('userNameDisplay');
        if (userNameDisplay) {
            const firstName = currentUser.name || currentUser.usr_name || 'Usuario';
            const lastName = currentUser.surname || currentUser.usr_surname || '';
            userNameDisplay.textContent = `${firstName} ${lastName}`.trim();
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
        .then(response => {
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('La respuesta no es JSON válido');
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            
            if (data.success) {
                console.log('Datos cargados exitosamente');
                updatePaymentsList(data.data.payments || []);
                updateHoursList(data.data.hours || []);
                updateHoursMonth(data.data.total_hours_month || 0, data.data.current_month || 'Mes Actual');
                
                // Actualizar información de pagos
                if (data.data.payment_info) {
                    updatePaymentSummary(data.data.payment_info);
                }
            } else {
                console.error('Error al cargar datos:', data.message);
                showMessage('paymentMessages', 'Error al cargar datos: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error de conexión:', error);
            showMessage('paymentMessages', 'Error de conexión. Verifica que el archivo API_cooperativa.php esté en el directorio correcto.', 'error');
        });
    }

    function updatePaymentSummary(paymentInfo) {
        // Actualizar saldo actual
        const currentBalanceEl = document.getElementById('currentBalance');
        const paymentStatusEl = document.getElementById('paymentStatus');
        const monthlyFeeEl = document.getElementById('monthlyFee');
        const progressFillEl = document.getElementById('progressFill');
        const progressTextEl = document.getElementById('progressText');
        
        if (currentBalanceEl) {
            currentBalanceEl.textContent = paymentInfo.balance_formatted || '$0';
        }
        
        if (paymentStatusEl) {
            paymentStatusEl.textContent = paymentInfo.payment_status || 'Desconocido';
            
            // Cambiar color según estado
            if (paymentInfo.payment_status === 'Al día') {
                paymentStatusEl.style.color = '#2e7d32';
            } else if (paymentInfo.payment_status === 'Pago parcial') {
                paymentStatusEl.style.color = '#f57c00';
            } else {
                paymentStatusEl.style.color = '#d32f2f';
            }
        }
        
        if (monthlyFeeEl) {
            monthlyFeeEl.textContent = paymentInfo.monthly_fee_formatted || '$22.000';
        }
        
        // Actualizar barra de progreso
        const progress = paymentInfo.payment_progress || 0;
        if (progressFillEl) {
            progressFillEl.style.width = progress + '%';
            
            // Cambiar color según progreso
            if (progress >= 100) {
                progressFillEl.style.background = 'linear-gradient(90deg, #4caf50 0%, #2e7d32 100%)';
            } else if (progress >= 50) {
                progressFillEl.style.background = 'linear-gradient(90deg, #ff9800 0%, #f57c00 100%)';
            } else {
                progressFillEl.style.background = 'linear-gradient(90deg, #f44336 0%, #d32f2f 100%)';
            }
        }
        
        if (progressTextEl) {
            progressTextEl.textContent = Math.round(progress) + '%';
        }
    }

    function submitPaymentForm(event) {
        event.preventDefault();
        
        const formData = new FormData();
        const form = document.getElementById('uploadPaymentForm');
        
        formData.append('user_id', currentUser.id);
        formData.append('payment_month', form.payment_month.value);
        formData.append('payment_year', form.payment_year.value);
        formData.append('payment_amount', form.payment_amount.value);
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
                loadUserData(); // Recargar datos para actualizar resumen
            } else {
                showMessage('paymentMessages', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('paymentMessages', 'Error al subir el comprobante', 'error');
        });
    }

    function submitBalanceForm(event) {
        event.preventDefault();
        
        const form = document.getElementById('balanceForm');
        const amount = parseFloat(form.balance_amount.value);
        const description = form.balance_description.value;
        
        if (!confirm(`¿Estás seguro de agregar ${amount.toLocaleString()} a tu saldo?`)) {
            return;
        }
        
        fetch(API_BASE + '?action=add_balance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: currentUser.id,
                amount: amount,
                description: description
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('paymentMessages', `Saldo agregado exitosamente. Nuevo saldo: ${data.data.balance_formatted}`, 'success');
                hideBalanceForm();
                form.reset();
                loadUserData(); // Recargar datos para actualizar resumen
            } else {
                showMessage('paymentMessages', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('paymentMessages', 'Error al agregar el saldo', 'error');
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
                loadUserData();
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
                loadUserData();
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
                loadUserData();
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
                <div class="empty-state" style="text-align: center; padding: 40px; color: #666;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 20px;">
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
            const fileIcon = payment.file_type && payment.file_type.includes('pdf') ? 
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
            
            const monthName = payment.month_name || 'Mes';
            const createdAt = payment.created_at_formatted || payment.created_at || 'Fecha desconocida';
            const fileSize = payment.file_size_display || 'Tamaño desconocido';
            const status = payment.status || 'pendiente';
            const amount = payment.payment_amount_formatted || '$0';
            
            let statusBadge = '';
            if (status === 'aprobado') {
                statusBadge = '<span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; text-transform: uppercase; background: #e8f5e8; color: #2e7d32;">Aprobado</span>';
            } else if (status === 'rechazado') {
                statusBadge = '<span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; text-transform: uppercase; background: #ffebee; color: #d32f2f;">Rechazado</span>';
            } else {
                statusBadge = '<span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; text-transform: uppercase; background: #fff3e0; color: #f57c00;">Pendiente</span>';
            }
            
            paymentsHTML += `
                <div class="payment-item" style="display: flex; justify-content: space-between; align-items: center; padding: 20px; border: 1px solid #e0e0e0; border-radius: 12px; margin-bottom: 15px; background: white;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div class="file-icon">${fileIcon}</div>
                        <div class="file-info">
                            <h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600;">Comprobante ${monthName} ${payment.payment_year}</h3>
                            <p style="margin: 0 0 4px 0; color: #666; font-size: 14px;">Importe: <strong style="color: #2e7d32;">${payment.payment_amount ? payment.payment_amount.toLocaleString() : '0'}</strong></p>
                            <p style="margin: 0 0 4px 0; color: #666; font-size: 14px;">Subido el ${createdAt} • ${fileSize}</p>
                            ${payment.description ? `<p style="font-style: italic; margin: 5px 0 0 0; color: #888; font-size: 13px;">${payment.description}</p>` : ''}
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        ${statusBadge}
                        <a href="${API_BASE}?action=download_payment&id=${payment.id}&user_id=${currentUser.id}" style="padding: 8px 16px; background: #2196f3; color: white; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500;">Descargar</a>
                        <button onclick="deletePayment(${payment.id})" style="padding: 8px 16px; background: #f44336; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">Eliminar</button>
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
            const workDate = record.work_date_formatted || record.work_date || 'Fecha desconocida';
            const createdAt = record.created_at_formatted || record.created_at || 'Fecha desconocida';
            const workType = record.work_type_display || record.work_type || 'Tipo desconocido';
            
            hoursHTML += `
                <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 20px; border: 1px solid #e0e0e0; border-radius: 12px; margin-bottom: 15px; background: white;">
                    <div style="flex-grow: 1;">
                        <h3 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600;">${workDate} - ${record.hours_worked} horas</h3>
                        <p style="margin: 0 0 8px 0; font-weight: 500; color: #d32f2f;">Tipo: ${workType}</p>
                        <p style="margin: 0 0 12px 0; color: #333; line-height: 1.5;">${record.description}</p>
                        <small style="color: #666; font-size: 13px;">Registrado el ${createdAt}</small>
                    </div>
                    <button onclick="deleteHours(${record.id})" style="padding: 8px 16px; background: #f44336; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; margin-left: 20px; font-weight: 500;">Eliminar</button>
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
        
        clearSession();
        window.location.href = 'index.php';
    }

    function logout() {
        if (confirm('¿Estás seguro que quieres cerrar sesión?')) {
            clearSession();
            alert('Sesión cerrada exitosamente');
            window.location.href = 'index.php';
        }
    }

    function goToAdmin() {
        if (currentUser && currentUser.is_admin == 1) {
            window.location.href = 'BACKOFFICE/admin.php';
        } else {
            alert('No tienes permisos para acceder al panel de administración');
        }
    }

    function openChat() {
        alert('Función de chat en desarrollo');
    }

    function showMessage(containerId, message, type) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        
        container.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
        
        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
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
        if (menuItems[0]) menuItems[0].textContent = favoriteTasks;
        if (menuItems[1]) menuItems[1].textContent = totalTasks;
        if (menuItems[2]) menuItems[2].textContent = casaTasks;
    }

    // Funciones de formularios
    function showUploadForm() {
        document.getElementById('upload-form').style.display = 'block';
        document.getElementById('balance-form').style.display = 'none';
    }

    function hideUploadForm() {
        document.getElementById('upload-form').style.display = 'none';
    }

    function showBalanceForm() {
        document.getElementById('balance-form').style.display = 'block';
        document.getElementById('upload-form').style.display = 'none';
    }

    function hideBalanceForm() {
        document.getElementById('balance-form').style.display = 'none';
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

            // Mostrar nombre del archivo seleccionado
            const fileInput = uploadArea.querySelector('input[type="file"]');
            fileInput.addEventListener('change', function() {
                const fileName = this.files[0]?.name || 'Ningún archivo seleccionado';
                uploadArea.querySelector('p').textContent = fileName;
            });
        }
    }

    // Inicializar componentes después de cargar la app
    setTimeout(() => {
        if (isUserDataLoaded) {
            setupFormHandlers();
            updateTaskCount();
        }
    }, 1000);
    </script>
</body>
</html>