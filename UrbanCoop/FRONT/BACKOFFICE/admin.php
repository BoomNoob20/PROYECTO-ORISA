<?php
session_start();

// Configuración de la base de datos con múltiples opciones
$db_configs = [
    // Configuración principal
    [
        'host' => 'localhost',
        'port' => '3306',
        'dbname' => 'usuarios_urban_coop',
        'username' => 'root',
        'password' => ''
    ],
    // Configuración alternativa (XAMPP)
    [
        'host' => '127.0.0.1',
        'port' => '3306',
        'dbname' => 'usuarios_urban_coop',
        'username' => 'root',
        'password' => ''
    ],
    // Configuración para WAMP
    [
        'host' => 'localhost',
        'port' => '3308',
        'dbname' => 'usuarios_urban_coop',
        'username' => 'root',
        'password' => ''
    ]
];

$pdo = null;
$connection_error = '';

// Intentar conectar con diferentes configuraciones
foreach ($db_configs as $config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        break; // Conexión exitosa
    } catch (PDOException $e) {
        $connection_error = $e->getMessage();
        continue; // Probar siguiente configuración
    }
}

// Si no hay conexión, mostrar error y no continuar
if (!$pdo) {
    $connection_error = "No se pudo conectar a la base de datos MySQL. Revisa la configuración.";
}

// Procesar acciones AJAX
if (isset($_POST['action']) && $pdo) {
    $response = ['success' => false, 'message' => ''];

    try {
        switch ($_POST['action']) {
            case 'approve_user':
                $user_id = (int) $_POST['user_id'];
                $stmt = $pdo->prepare("UPDATE usuario SET estado = 2 WHERE id = ?");
                if ($stmt->execute([$user_id])) {
                    $response = ['success' => true, 'message' => 'Usuario aprobado correctamente'];
                }
                break;

            case 'reject_user':
                $user_id = (int) $_POST['user_id'];
                $stmt = $pdo->prepare("UPDATE usuario SET estado = 3 WHERE id = ?");
                if ($stmt->execute([$user_id])) {
                    $response = ['success' => true, 'message' => 'Usuario rechazado correctamente'];
                }
                break;

            case 'approve_payment':
                $payment_id = (int) $_POST['payment_id'];
                $stmt = $pdo->prepare("UPDATE comprobantes_pago SET status = 'aprobado' WHERE id = ?");
                if ($stmt->execute([$payment_id])) {
                    $response = ['success' => true, 'message' => 'Comprobante aprobado correctamente'];
                }
                break;

            case 'reject_payment':
                $payment_id = (int) $_POST['payment_id'];
                $stmt = $pdo->prepare("UPDATE comprobantes_pago SET status = 'rechazado' WHERE id = ?");
                if ($stmt->execute([$payment_id])) {
                    $response = ['success' => true, 'message' => 'Comprobante rechazado correctamente'];
                }
                break;

            case 'approve_hours':
                $hours_id = (int) $_POST['hours_id'];
                $stmt = $pdo->prepare("UPDATE horas_trabajadas SET description = CONCAT(description, ' [APROBADO]') WHERE id = ? AND description NOT LIKE '%[APROBADO]%'");
                if ($stmt->execute([$hours_id])) {
                    $response = ['success' => true, 'message' => 'Horas aprobadas correctamente'];
                }
                break;

            case 'reject_hours':
                $hours_id = (int) $_POST['hours_id'];
                $stmt = $pdo->prepare("UPDATE horas_trabajadas SET description = CONCAT(description, ' [RECHAZADO]') WHERE id = ? AND description NOT LIKE '%[RECHAZADO]%'");
                if ($stmt->execute([$hours_id])) {
                    $response = ['success' => true, 'message' => 'Horas rechazadas correctamente'];
                }
                break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Inicializar variables
$pending_users = [];
$pending_payments = [];
$pending_hours = [];
$all_users = [];
$error_message = '';
$connection_status = '';

// Solo obtener datos si hay conexión a la base de datos
if ($pdo) {
    try {
        // Obtener usuarios pendientes (estado = 1) usando la tabla usuario
        $pending_users = $pdo->query("
            SELECT id, usr_name as nombre, usr_surname as apellido, usr_email as email, 
                   usr_phone as telefono, estado, usr_ci as cedula
            FROM usuario 
            WHERE estado = 1 
            ORDER BY id DESC
        ")->fetchAll();

        // Obtener todos los usuarios para debug
        $all_users = $pdo->query("
            SELECT id, usr_email as email, estado, usr_name as nombre, usr_surname as apellido 
            FROM usuario 
            ORDER BY id DESC
        ")->fetchAll();

        // Obtener comprobantes pendientes
        $pending_payments = $pdo->query("
            SELECT cp.*, u.usr_name as nombre, u.usr_surname as apellido 
            FROM comprobantes_pago cp 
            JOIN usuario u ON cp.user_id = u.id 
            WHERE cp.status = 'pendiente' 
            ORDER BY cp.id DESC
        ")->fetchAll();

        // Obtener horas trabajadas pendientes
        $pending_hours = $pdo->query("
            SELECT ht.*, u.usr_name as nombre, u.usr_surname as apellido 
            FROM horas_trabajadas ht 
            JOIN usuario u ON ht.user_id = u.id 
            WHERE ht.description NOT LIKE '%[APROBADO]%' 
            AND ht.description NOT LIKE '%[RECHAZADO]%'
            ORDER BY ht.id DESC
        ")->fetchAll();

    } catch (Exception $e) {
        $error_message = "Error al obtener datos: " . $e->getMessage();
    }
} else {
    $error_message = $connection_error;
}

// Función para formatear la fecha
function formatDate($date)
{
    if (!$date)
        return 'No especificado';
    return date('d/m/Y H:i', strtotime($date));
}

// Función para formatear la fecha simple
function formatDateSimple($date)
{
    if (!$date)
        return 'No especificado';
    return date('d/m/Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Urban Coop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/adminStyles.css">
</head>

<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="logo-text">URBAN COOP</div>
            </div>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a class="nav-link" onclick="showSection('Info')" id="Info-nav">
                        <i class="fas fa-user-plus nav-icon"></i>
                        Info
                        <span class="badge"><?= count($pending_users) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" onclick="showSection('payments')" id="payments-nav">
                        <i class="fas fa-receipt nav-icon"></i>
                        Pagos
                        <span class="badge"><?= count($pending_payments) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" onclick="showSection('hours')" id="hours-nav">
                        <i class="fas fa-clock nav-icon"></i>
                        Horas
                        <span class="badge"><?= count($pending_hours) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" onclick="showSection('users')" id="users-nav">
                        <i class="fas fa-user-plus nav-icon"></i>
                        Trabajadores en Espera
                        <span class="badge"><?= count($pending_users) ?></span>
                    </a>
                </li>
                <?php if (!$pdo): ?>
                    <li class="nav-item">
                        <a class="nav-link" onclick="showSection('setup')" id="setup-nav">
                            <i class="fas fa-database nav-icon"></i>
                            Configurar BD
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" onclick="showSection('debug')" id="debug-nav">
                        <i class="fas fa-bug nav-icon"></i>
                        Información Debug
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 id="page-title">Trabajadores en Espera</h1>
                </div>
                <div class="header-actions">
                    <div class="user-menu">
                        <i class="fas fa-user-circle"></i>
                        <span>Admin</span>
                    </div>
                </div>
            </header>

            <div class="content">
                <!-- Alerts -->
                <div class="alert alert-success" id="success-alert"></div>
                <div class="alert alert-error" id="error-alert"></div>

                <?php if ($error_message): ?>
                    <div class="alert alert-warning" style="display: block;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$pdo): ?>
                    <!-- Error de conexión -->
                    <div class="error-state">
                        <i class="fas fa-database"></i>
                        <h3>Sin Conexión a la Base de Datos</h3>
                        <p>No se puede conectar a MySQL. Revisa la configuración o configura la base de datos.</p>
                        <button class="btn btn-view" onclick="showSection('setup')">
                            <i class="fas fa-cog"></i> Ir a Configuración
                        </button>
                    </div>

                    <!-- Database Setup Section -->
                    <div class="section" id="setup-section">
                        <div class="db-setup">
                            <h3><i class="fas fa-database"></i> Configuración de Base de Datos</h3>

                            <div class="setup-step">
                                <h4>Ejecuta este SQL en tu base de datos MySQL:</h4>
                                <div class="code-block">
                                    -- Crear base de datos
                                    CREATE DATABASE IF NOT EXISTS usuarios_urban_coop
                                    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

                                    USE usuarios_urban_coop;

                                    -- Crear tabla usuario
                                    CREATE TABLE IF NOT EXISTS usuario (
                                    id INT(11) NOT NULL AUTO_INCREMENT,
                                    usr_name VARCHAR(100) NOT NULL COMMENT 'Nombre del usuario',
                                    usr_surname VARCHAR(100) NOT NULL COMMENT 'Apellido del usuario',
                                    usr_email VARCHAR(100) NOT NULL COMMENT 'Email del usuario',
                                    usr_pass VARCHAR(100) NOT NULL COMMENT 'Contraseña',
                                    usr_ci INT(11) NOT NULL COMMENT 'Cédula de identidad',
                                    usr_phone INT(11) NOT NULL COMMENT 'Teléfono',
                                    is_admin INT(11) NOT NULL DEFAULT 0 COMMENT '0=Usuario normal, 1=Administrador',
                                    estado INT(11) NOT NULL DEFAULT 1 COMMENT '1=Pendiente, 2=Aprobado, 3=Rechazado',
                                    PRIMARY KEY (id),
                                    UNIQUE KEY usr_email (usr_email),
                                    UNIQUE KEY usr_ci (usr_ci),
                                    INDEX idx_estado (estado),
                                    INDEX idx_admin (is_admin)
                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                                    -- Crear tabla horas trabajadas
                                    CREATE TABLE IF NOT EXISTS horas_trabajadas (
                                    id INT NOT NULL AUTO_INCREMENT,
                                    user_id INT NOT NULL,
                                    work_date DATE NOT NULL,
                                    hours_worked DECIMAL(4,2) NOT NULL,
                                    description TEXT NOT NULL,
                                    work_type VARCHAR(50) NOT NULL,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    PRIMARY KEY (id),
                                    FOREIGN KEY (user_id) REFERENCES usuario(id) ON DELETE CASCADE,
                                    UNIQUE KEY unique_user_date (user_id, work_date)
                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                                    -- Crear tabla comprobantes de pago
                                    CREATE TABLE IF NOT EXISTS comprobantes_pago (
                                    id INT NOT NULL AUTO_INCREMENT,
                                    user_id INT NOT NULL,
                                    payment_month VARCHAR(2) NOT NULL,
                                    payment_year VARCHAR(4) NOT NULL,
                                    file_name VARCHAR(255) NOT NULL,
                                    file_path VARCHAR(500) NOT NULL,
                                    file_size INT NOT NULL,
                                    file_type VARCHAR(50) NOT NULL,
                                    description TEXT,
                                    status ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (id),
                                    FOREIGN KEY (user_id) REFERENCES usuario(id) ON DELETE CASCADE,
                                    UNIQUE KEY unique_user_month_year (user_id, payment_month, payment_year)
                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                                    -- Insertar usuarios de ejemplo
                                    INSERT INTO usuario(usr_name, usr_surname, usr_email, usr_pass, usr_ci, usr_phone,
                                    is_admin, estado) VALUES
                                    ('Pedro','Garfhone','usuario@gmail.com','123456',24966853,93658842,0,1),
                                    ('Admin','Istrador','admin@gmail.com','admin123',12345678,99999999,1,2)
                                    ON DUPLICATE KEY UPDATE usr_name = VALUES(usr_name);
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Debug Section -->
                    <div class="section" id="debug-section">
                        <div class="debug-info">
                            <div class="debug-title"><i class="fas fa-info-circle"></i> Información de Debug</div>
                            <p><strong>Estado de conexión:</strong> <span id="connection-status"></span> </p>
                            <p><strong>Total usuarios en el sistema:</strong> <?= count($all_users) ?></p>
                            <p><strong>Usuarios pendientes (estado = 1):</strong> <?= count($pending_users) ?></p>
                            <p><strong>Comprobantes pendientes:</strong> <?= count($pending_payments) ?></p>
                            <p><strong>Horas registradas (pendientes):</strong> <?= count($pending_hours) ?></p>

                            <?php if (!empty($all_users)): ?>
                                <details style="margin-top: 15px;">
                                    <summary style="cursor: pointer; font-weight: 600; padding: 5px 0;">
                                        <i class="fas fa-users"></i> Ver todos los usuarios (<?= count($all_users) ?>)
                                    </summary>
                                    <div
                                        style="margin-top: 10px; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                                        <?php foreach ($all_users as $user): ?>
                                            <div
                                                style="padding: 8px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
                                                <span><strong>ID:</strong> <?= $user['id'] ?> | <strong>Nombre:</strong>
                                                    <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?> |
                                                    <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></span>
                                                <span
                                                    class="status-badge <?= $user['estado'] == 1 ? 'status-pending' : ($user['estado'] == 2 ? 'status-approved' : 'status-rejected') ?>">
                                                    <?= $user['estado'] == 1 ? 'Pendiente' : ($user['estado'] == 2 ? 'Aprobado' : 'Rechazado') ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Users Section -->
                    <div class="section active" id="users-section">
                        <?php if (empty($pending_users)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-check"></i>
                                <h3>No hay trabajadores pendientes</h3>
                                <p>Todos los trabajadores han sido procesados o no hay usuarios registrados</p>
                            </div>
                        <?php else: ?>
                            <div class="task-list">
                                <?php foreach ($pending_users as $user): ?>
                                    <div class="task-card" id="user-card-<?= $user['id'] ?>">
                                        <div class="task-header">
                                            <div class="task-checkbox" onclick="toggleCheckbox(this)"></div>
                                            <div class="task-info">
                                                <div class="task-title">
                                                    <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?>
                                                </div>
                                                <div class="task-details">
                                                    <div class="task-detail">
                                                        <i class="fas fa-envelope"></i>
                                                        <span><?= htmlspecialchars($user['email']) ?></span>
                                                    </div>
                                                    <div class="task-detail">
                                                        <i class="fas fa-phone"></i>
                                                        <span><?= htmlspecialchars($user['telefono'] ?: 'No especificado') ?></span>
                                                    </div>
                                                    <div class="task-detail">
                                                        <i class="fas fa-id-card"></i>
                                                        <span>CI:
                                                            <?= htmlspecialchars($user['cedula'] ?: 'No especificado') ?></span>
                                                    </div>
                                                    <div class="task-detail">
                                                        <i class="fas fa-user"></i>
                                                        <span>ID: <?= $user['id'] ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="star-action" onclick="toggleStar(this)">
                                                <i class="far fa-star"></i>
                                            </div>
                                        </div>

                                        <div class="task-actions">
                                            <button class="btn btn-approve"
                                                onclick="processUser(<?= $user['id'] ?>, 'approve', this)">
                                                <i class="fas fa-check"></i> Aceptar
                                            </button>
                                            <button class="btn btn-reject"
                                                onclick="processUser(<?= $user['id'] ?>, 'reject', this)">
                                                <i class="fas fa-times"></i> Rechazar
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Payments Section -->
                    <div class="section" id="payments-section">
                        <?php if (empty($pending_payments)): ?>
                            <div class="empty-state">
                                <i class="fas fa-receipt"></i>
                                <h3>No hay comprobantes pendientes</h3>
                                <p>Todos los comprobantes han sido procesados o no hay comprobantes registrados</p>
                            </div>
                        <?php else: ?>
                            <div class="task-list">
                                <?php foreach ($pending_payments as $payment): ?>
                                    <div class="task-card" id="payment-card-<?= $payment['id'] ?>">
                                        <div class="task-header">
                                            <div class="task-checkbox" onclick="toggleCheckbox(this)"></div>
                                            <div class="task-info">
                                                <div class="task-title">Comprobante #<?= $payment['id'] ?> -
                                                    <?= htmlspecialchars($payment['nombre'] . ' ' . $payment['apellido']) ?>
                                                </div>
                                                <div class="task-details">
                                                    <div class="task-detail">
                                                        <i class="fas fa-calendar"></i>
                                                        <span><?= htmlspecialchars($payment['payment_month']) ?>/<?= htmlspecialchars($payment['payment_year']) ?></span>
                                                    </div>
                                                    <div class="task-detail">
                                                        <i class="fas fa-file"></i>
                                                        <span><?= htmlspecialchars($payment['file_name']) ?></span>
                                                    </div>
                                                    <div class="task-detail">
                                                        <i class="fas fa-weight"></i>
                                                        <span><?= number_format($payment['file_size'] / 1024, 2) ?> KB</span>
                                                    </div>
                                                    <div class="task-detail">
                                                        <i class="fas fa-clock"></i>
                                                        <span><?= formatDate($payment['created_at']) ?></span>
                                                    </div>
                                                    <?php if ($payment['description']): ?>
                                                        <div class="task-detail">
                                                            <i class="fas fa-comment"></i>
                                                            <span><?= htmlspecialchars($payment['description']) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="star-action" onclick="toggleStar(this)">
                                                <i class="far fa-star"></i>
                                            </div>
                                        </div>

                                        <div class="task-actions">
                                            <?php if ($payment['file_path']): ?>
                                                <a href="<?= htmlspecialchars($payment['file_path']) ?>" target="_blank"
                                                    class="btn btn-view">
                                                    <i class="fas fa-eye"></i> Ver Archivo
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-approve"
                                                onclick="processPayment(<?= $payment['id'] ?>, 'approve', this)">
                                                <i class="fas fa-check"></i> Aprobar
                                            </button>
                                            <button class="btn btn-reject"
                                                onclick="processPayment(<?= $payment['id'] ?>, 'reject', this)">
                                                <i class="fas fa-times"></i> Rechazar
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Hours Section -->
                    <div class="section" id="hours-section">
                        <?php if (empty($pending_hours)): ?>
                            <div class="empty-state">
                                <i class="fas fa-clock"></i>
                                <h3>No hay horas pendientes</h3>
                                <p>Todas las horas han sido procesadas o no hay registros de horas</p>
                            </div>
                        <?php else: ?>
                            <div class="task-list">
                                <?php foreach ($pending_hours as $hours): ?>
                                    <div class="task-card" id="hours-card-<?= $hours['id'] ?>">
                                        <div class="task-header">
                                            <div class="task-checkbox" onclick="toggleCheckbox(this)"></div>
                                            <div class="task-info">
                                                <div class="task-title">
                                                    Registro #<?= $hours['id'] ?> -
                                                    <?= htmlspecialchars($hours['nombre'] . ' ' . $hours['apellido']) ?>
                                                </div>
                                                <div class="task-details">
                                                    <div class="task-detail">
                                                        <i class="fas fa-clock"></i>
                                                        <span><?= $hours['hours_worked'] ?> horas</span>
                                                    </div>
                                                    <div class="task-detail">
                                                        <i class="fas fa-calendar"></i>
                                                        <span><?= formatDateSimple($hours['work_date']) ?></span>
                                                    </div>
                                                    <div class="task-detail">
                                                        <i class="fas fa-tools"></i>
                                                        <span><?= htmlspecialchars($hours['work_type']) ?></span>
                                                    </div>
                                                    <div class="task-detail">
                                                        <i class="fas fa-clock"></i>
                                                        <span>Registrado: <?= formatDate($hours['created_at']) ?></span>
                                                    </div>
                                                    <div class="task-detail">
                                                        <i class="fas fa-comment"></i>
                                                        <span><?= htmlspecialchars($hours['description']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="star-action" onclick="toggleStar(this)">
                                                <i class="far fa-star"></i>
                                            </div>
                                        </div>

                                        <div class="task-actions">
                                            <button class="btn btn-approve"
                                                onclick="processHours(<?= $hours['id'] ?>, 'approve', this)">
                                                <i class="fas fa-check"></i> Aprobar
                                            </button>
                                            <button class="btn btn-reject"
                                                onclick="processHours(<?= $hours['id'] ?>, 'reject', this)">
                                                <i class="fas fa-times"></i> Rechazar
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Botón flotante para ir al perfil -->
    <button class="profile-btn" onclick="goToProfile()" title="Ir al Perfil">
        <i class="fas fa-user"></i>
    </button>

    <style>
        .profile-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
        }

        .profile-btn:active {
            transform: translateY(0);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        /* Responsive para móvil */
        @media (max-width: 768px) {
            .profile-btn {
                width: 50px;
                height: 50px;
                font-size: 16px;
                bottom: 15px;
                left: 15px;
            }
        }
    </style>

    <script>
        function goToProfile() {
            // Verificar que el usuario esté logueado antes de redirigir
            const userData = sessionStorage.getItem('user_data');

            if (!userData) {
                alert('Error: No se encontró información de sesión');
                document.location = '../loginLP.php';
                return;
            }

            // Redirigir al perfil
            window.location.href = '../perfil.php';
        }
    </script>

    <!-- AGREGAR AQUÍ EL CÓDIGO DE SESIÓN -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // VERIFICAR SI EL USUARIO ESTÁ LOGUEADO
            const userData = sessionStorage.getItem('user_data');

            if (!userData) {
                alert('Debes iniciar sesión para acceder a esta página');
                document.location = 'loginLP.php';
                return;
            }

            const user = JSON.parse(userData);

            // VERIFICAR PERMISOS DE ADMIN
            if (user.is_admin != 1) {
                alert('No tienes permisos para acceder al panel de administración');
                document.location = 'perfil.php';
                return;
            }

            //  MOSTRAR INFORMACIÓN DEL USUARIO ADMIN
            console.log(' Admin actual:');
            console.log('ID:', user.id);
            console.log('Nombre completo:', user.name + ' ' + user.surname);
            console.log('Email:', user.email);
            console.log('Es Admin:', user.is_admin);

            // ACTUALIZAR ELEMENTOS DE LA PÁGINA
            // Actualizar nombre del admin en el header
            const userMenu = document.querySelector('.user-menu span');
            if (userMenu) {
                userMenu.textContent = user.name + ' ' + user.surname;
            }

        });

        //  FUNCIONES AUXILIARES
        function getCurrentUser() {
            const userData = sessionStorage.getItem('user_data');
            return userData ? JSON.parse(userData) : null;
        }

        function getCurrentUserId() {
            const user = getCurrentUser();
            return user ? user.id : null;
        }

        function isAdmin() {
            const user = getCurrentUser();
            return user && user.is_admin == 1;
        }

        function logout() {
            if (confirm('¿Estás seguro que quieres cerrar sesión?')) {
                sessionStorage.clear();
                alert('Sesión cerrada exitosamente');
                document.location = 'loginLP.php';
            }
        }
    </script>


    <script>
        // Variables globales
        let currentSection = 'users';

        function showSection(sectionName) {
            // Ocultar todas las secciones
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });

            // Remover clase active de todos los links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });

            // Mostrar la sección seleccionada
            const section = document.getElementById(sectionName + '-section');
            if (section) {
                section.classList.add('active');
            }

            // Activar el link correspondiente
            const navLink = document.getElementById(sectionName + '-nav');
            if (navLink) {
                navLink.classList.add('active');
            }

            // Actualizar título
            const titles = {
                'users': 'Trabajadores en Espera',
                'payments': 'Comprobantes de Pago',
                'hours': 'Horas Trabajadas',
                'setup': 'Configurar Base de Datos',
                'debug': 'Información Debug'
            };

            const titleElement = document.getElementById('page-title');
            if (titleElement && titles[sectionName]) {
                titleElement.textContent = titles[sectionName];
            }

            currentSection = sectionName;

            // Cerrar sidebar en móvil
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('open');
            }
        }

        function showAlert(message, type) {
            const alert = document.getElementById(type + '-alert');
            if (alert) {
                alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
                alert.style.display = 'block';

                // Auto-hide after 5 seconds
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);

                // Scroll to alert
                alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function addLoadingState(button) {
            if (button) {
                button.disabled = true;
                const originalContent = button.innerHTML;
                button.innerHTML = '<div class="spinner"></div> Procesando...';

                return function removeLoadingState() {
                    button.disabled = false;
                    button.innerHTML = originalContent;
                };
            }
            return function () { };
        }

        function processUser(userId, action, button) {
            const removeLoading = addLoadingState(button);
            const actionText = action === 'approve' ? 'approve_user' : 'reject_user';

            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${actionText}&user_id=${userId}`
            })
                .then(response => response.json())
                .then(data => {
                    removeLoading();
                    if (data.success) {
                        showAlert(data.message, 'success');
                        const card = document.getElementById(`user-card-${userId}`);
                        if (card) {
                            card.style.transform = 'translateX(100%)';
                            card.style.opacity = '0';
                            setTimeout(() => {
                                card.style.display = 'none';
                                updateBadge('users-nav');
                                checkEmptyState('users-section');
                            }, 300);
                        }
                    } else {
                        showAlert(data.message || 'Error al procesar usuario', 'error');
                    }
                })
                .catch(error => {
                    removeLoading();
                    showAlert('Error de conexión', 'error');
                    console.error('Error:', error);
                });
        }

        function processPayment(paymentId, action, button) {
            const removeLoading = addLoadingState(button);
            const actionText = action === 'approve' ? 'approve_payment' : 'reject_payment';

            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${actionText}&payment_id=${paymentId}`
            })
                .then(response => response.json())
                .then(data => {
                    removeLoading();
                    if (data.success) {
                        showAlert(data.message, 'success');
                        const card = document.getElementById(`payment-card-${paymentId}`);
                        if (card) {
                            card.style.transform = 'translateX(100%)';
                            card.style.opacity = '0';
                            setTimeout(() => {
                                card.style.display = 'none';
                                updateBadge('payments-nav');
                                checkEmptyState('payments-section');
                            }, 300);
                        }
                    } else {
                        showAlert(data.message || 'Error al procesar comprobante', 'error');
                    }
                })
                .catch(error => {
                    removeLoading();
                    showAlert('Error de conexión', 'error');
                    console.error('Error:', error);
                });
        }

        function processHours(hoursId, action, button) {
            const removeLoading = addLoadingState(button);
            const actionText = action === 'approve' ? 'approve_hours' : 'reject_hours';

            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${actionText}&hours_id=${hoursId}`
            })
                .then(response => response.json())
                .then(data => {
                    removeLoading();
                    if (data.success) {
                        showAlert(data.message, 'success');
                        const card = document.getElementById(`hours-card-${hoursId}`);
                        if (card) {
                            card.style.transform = 'translateX(100%)';
                            card.style.opacity = '0';
                            setTimeout(() => {
                                card.style.display = 'none';
                                updateBadge('hours-nav');
                                checkEmptyState('hours-section');
                            }, 300);
                        }
                    } else {
                        showAlert(data.message || 'Error al procesar horas', 'error');
                    }
                })
                .catch(error => {
                    removeLoading();
                    showAlert('Error de conexión', 'error');
                    console.error('Error:', error);
                });
        }

        function updateBadge(navId) {
            const badge = document.querySelector(`#${navId} .badge`);
            if (badge) {
                let count = parseInt(badge.textContent) - 1;
                badge.textContent = count < 0 ? 0 : count;

                // Add animation
                badge.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    badge.style.transform = 'scale(1)';
                }, 200);
            }
        }

        function checkEmptyState(sectionId) {
            const section = document.getElementById(sectionId);
            const taskList = section.querySelector('.task-list');
            const visibleCards = taskList ? taskList.querySelectorAll('.task-card:not([style*="display: none"])') : [];

            if (visibleCards.length === 0) {
                const emptyStateHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3>¡Todo procesado!</h3>
                        <p>No hay elementos pendientes en esta sección</p>
                    </div>
                `;

                if (taskList) {
                    taskList.innerHTML = emptyStateHTML;
                }
            }
        }

        function toggleCheckbox(checkbox) {
            const isChecked = checkbox.style.backgroundColor === 'rgb(211, 47, 47)';

            if (isChecked) {
                checkbox.style.backgroundColor = '';
                checkbox.innerHTML = '';
                checkbox.style.borderColor = '#d0d0d3';
            } else {
                checkbox.style.backgroundColor = '#d32f2f';
                checkbox.style.borderColor = '#d32f2f';
                checkbox.innerHTML = '<i class="fas fa-check" style="color: white; font-size: 12px;"></i>';
            }
        }

        function toggleStar(starElement) {
            const icon = starElement.querySelector('i');
            const isStarred = icon.classList.contains('fas');

            if (isStarred) {
                icon.classList.remove('fas');
                icon.classList.add('far');
                starElement.classList.remove('starred');
            } else {
                icon.classList.remove('far');
                icon.classList.add('fas');
                starElement.classList.add('starred');
            }

            // Add animation
            starElement.style.transform = 'scale(1.2)';
            setTimeout(() => {
                starElement.style.transform = 'scale(1)';
            }, 200);
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function () {
            // Animate cards on load
            const cards = document.querySelectorAll('.task-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function (e) {
                if (window.innerWidth <= 768) {
                    const sidebar = document.getElementById('sidebar');
                    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');

                    if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                        sidebar.classList.remove('open');
                    }
                }
            });

            // Handle window resize
            window.addEventListener('resize', function () {
                if (window.innerWidth > 768) {
                    document.getElementById('sidebar').classList.remove('open');
                }
            });
        });


        // CÓDIGO DE CONECION TESTING DE APIS
        const data = {
            email: "Pedro@gmail.com",
            password: "123456"
        };

        console.log('Enviando datos:', data);

        fetch('../../APIS/API_Usuarios.php?action=login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(response => {

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                    // Modificamos el texto de conection status
                    const statusElement = document.getElementById('connection-status');
                    statusElement.textContent = 'Conectado';
            })
            .catch(error => {
                // Modificamos el texto de conection status
                    const statusElement = document.getElementById('connection-status');
                    statusElement.textContent = 'Sin conexión3';
            });

    </script>
</body>

</html>