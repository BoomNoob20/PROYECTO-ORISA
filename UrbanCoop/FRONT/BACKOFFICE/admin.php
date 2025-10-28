<?php
session_start();

// Configuración de la base de datos con múltiples opciones
$db_configs = [
    ['host' => 'localhost', 'port' => '3306', 'dbname' => 'usuarios_urban_coop', 'username' => 'root', 'password' => ''],
    ['host' => '127.0.0.1', 'port' => '3306', 'dbname' => 'usuarios_urban_coop', 'username' => 'root', 'password' => ''],
    ['host' => 'localhost', 'port' => '3308', 'dbname' => 'usuarios_urban_coop', 'username' => 'root', 'password' => '']
];

$pdo = null;
$connection_error = '';

foreach ($db_configs as $config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        break;
    } catch (PDOException $e) {
        $connection_error = $e->getMessage();
        continue;
    }
}

if (!$pdo) {
    $connection_error = "No se pudo conectar a la base de datos MySQL.";
}

// Crear tablas si no existen
if ($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS reuniones (
            id INT NOT NULL AUTO_INCREMENT,
            titulo VARCHAR(200) NOT NULL,
            descripcion TEXT,
            fecha_reunion DATETIME NOT NULL,
            lugar VARCHAR(200),
            estado ENUM('programada', 'realizada', 'cancelada') DEFAULT 'programada',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_fecha (fecha_reunion),
            INDEX idx_estado (estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS unidades (
            id INT NOT NULL AUTO_INCREMENT,
            numero_unidad VARCHAR(50) NOT NULL,
            bloque VARCHAR(50),
            piso INT,
            cuartos INT NOT NULL,
            banos INT NOT NULL,
            tamano DECIMAL(8,2) NOT NULL COMMENT 'Tamaño en m2',
            capacidad INT NOT NULL COMMENT 'Capacidad de personas (2, 4 o 6)',
            tipo_unidad ENUM('apartamento', 'casa', 'local') DEFAULT 'apartamento',
            estado ENUM('disponible', 'ocupada', 'mantenimiento') DEFAULT 'disponible',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_unidad (numero_unidad, bloque),
            INDEX idx_estado (estado),
            INDEX idx_capacidad (capacidad)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS asignaciones_unidades (
            id INT NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL,
            unidad_id INT NOT NULL,
            fecha_asignacion DATE NOT NULL,
            fecha_finalizacion DATE NULL,
            estado ENUM('activa', 'finalizada') DEFAULT 'activa',
            notas TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (user_id) REFERENCES usuario(id) ON DELETE CASCADE,
            FOREIGN KEY (unidad_id) REFERENCES unidades(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_unidad_id (unidad_id),
            INDEX idx_estado (estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {
        // Tablas ya existen
    }
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

            case 'create_meeting':
                $titulo = $_POST['titulo'];
                $descripcion = $_POST['descripcion'];
                $fecha = $_POST['fecha_reunion'];
                $lugar = $_POST['lugar'];
                $stmt = $pdo->prepare("INSERT INTO reuniones (titulo, descripcion, fecha_reunion, lugar) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$titulo, $descripcion, $fecha, $lugar])) {
                    $response = ['success' => true, 'message' => 'Reunión creada correctamente'];
                }
                break;

            case 'create_unit':
                $numero_unidad = $_POST['numero_unidad'];
                $bloque = $_POST['bloque'];
                $piso = (int) $_POST['piso'];
                $cuartos = (int) $_POST['cuartos'];
                $banos = (int) $_POST['banos'];
                $tamano = (float) $_POST['tamano'];
                $capacidad = (int) $_POST['capacidad'];
                $tipo_unidad = $_POST['tipo_unidad'];
                
                $stmt = $pdo->prepare("INSERT INTO unidades (numero_unidad, bloque, piso, cuartos, banos, tamano, capacidad, tipo_unidad) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$numero_unidad, $bloque, $piso, $cuartos, $banos, $tamano, $capacidad, $tipo_unidad])) {
                    $response = ['success' => true, 'message' => 'Unidad creada correctamente'];
                }
                break;

            case 'assign_unit':
                $user_id = (int) $_POST['user_id'];
                $unidad_id = (int) $_POST['unidad_id'];
                $fecha_asignacion = $_POST['fecha_asignacion'];
                $notas = $_POST['notas'] ?? '';
                
                // Verificar que la unidad esté disponible
                $stmt = $pdo->prepare("SELECT estado FROM unidades WHERE id = ?");
                $stmt->execute([$unidad_id]);
                $unidad = $stmt->fetch();
                
                if ($unidad && $unidad['estado'] === 'disponible') {
                    // Crear asignación
                    $stmt = $pdo->prepare("INSERT INTO asignaciones_unidades (user_id, unidad_id, fecha_asignacion, notas) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$user_id, $unidad_id, $fecha_asignacion, $notas])) {
                        // Actualizar estado de la unidad
                        $pdo->prepare("UPDATE unidades SET estado = 'ocupada' WHERE id = ?")->execute([$unidad_id]);
                        $response = ['success' => true, 'message' => 'Unidad asignada correctamente'];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'La unidad no está disponible'];
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

// Obtener datos
$pending_users = [];
$pending_payments = [];
$pending_hours = [];
$upcoming_meetings = [];
$all_users = [];
$approved_users = [];
$available_units = [];
$all_units = [];

if ($pdo) {
    try {
        $pending_users = $pdo->query("SELECT id, usr_name as nombre, usr_surname as apellido, usr_email as email, usr_phone as telefono, estado, usr_ci as cedula FROM usuario WHERE estado = 1 ORDER BY id DESC")->fetchAll();
        
        $all_users = $pdo->query("SELECT id, usr_email as email, estado, usr_name as nombre, usr_surname as apellido FROM usuario ORDER BY id DESC")->fetchAll();
        
        $approved_users = $pdo->query("SELECT id, usr_name as nombre, usr_surname as apellido FROM usuario WHERE estado = 2 ORDER BY usr_name")->fetchAll();
        
        $pending_payments = $pdo->query("SELECT cp.*, u.usr_name as nombre, u.usr_surname as apellido FROM comprobantes_pago cp JOIN usuario u ON cp.user_id = u.id WHERE cp.status = 'pendiente' ORDER BY cp.id DESC")->fetchAll();
        
        $pending_hours = $pdo->query("SELECT ht.*, u.usr_name as nombre, u.usr_surname as apellido FROM horas_trabajadas ht JOIN usuario u ON ht.user_id = u.id WHERE ht.description NOT LIKE '%[APROBADO]%' AND ht.description NOT LIKE '%[RECHAZADO]%' ORDER BY ht.id DESC")->fetchAll();
        
        $upcoming_meetings = $pdo->query("SELECT * FROM reuniones WHERE fecha_reunion >= NOW() AND estado = 'programada' ORDER BY fecha_reunion ASC LIMIT 10")->fetchAll();
        
        $available_units = $pdo->query("SELECT * FROM unidades WHERE estado = 'disponible' ORDER BY bloque, numero_unidad")->fetchAll();
        
        $all_units = $pdo->query("SELECT * FROM unidades ORDER BY created_at DESC")->fetchAll();
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

function formatDate($date) {
    if (!$date) return 'No especificado';
    return date('d/m/Y H:i', strtotime($date));
}

function formatDateSimple($date) {
    if (!$date) return 'No especificado';
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
    <style>
        /* Liquid Glass Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #d32f2f, #f44336);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .glass-card-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #d32f2f, #f44336);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            margin-bottom: 15px;
        }

        .glass-card-number {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin: 10px 0;
        }

        .glass-card-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Accordion Styles */
        .nav-accordion {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-accordion-item {
            margin-bottom: 5px;
        }

        .nav-accordion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 8px;
            color: #666;
        }

        .nav-accordion-header:hover {
            background: rgba(211, 47, 47, 0.1);
            color: #d32f2f;
        }

        .nav-accordion-header.active {
            background: rgba(211, 47, 47, 0.1);
            color: #d32f2f;
        }

        .accordion-arrow {
            transition: transform 0.3s ease;
            font-size: 12px;
        }

        .accordion-arrow.rotated {
            transform: rotate(180deg);
        }

        .nav-accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding-left: 20px;
        }

        .nav-accordion-content.open {
            max-height: 500px;
        }

        .nav-sub-link {
            display: block;
            padding: 10px 20px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 3px 0;
        }

        .nav-sub-link:hover {
            background: rgba(211, 47, 47, 0.05);
            color: #d32f2f;
            padding-left: 25px;
        }

        .nav-sub-link.active {
            background: rgba(211, 47, 47, 0.1);
            color: #d32f2f;
            font-weight: 600;
        }

        /* Meeting Card */
        .meeting-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #d32f2f;
            transition: transform 0.2s ease;
        }

        .meeting-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .meeting-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .meeting-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .meeting-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #d32f2f;
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: white;
            border-radius: 6px;
            font-size: 14px;
        }

        .detail-item i {
            font-size: 16px;
        }

        .status-approved {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-rejected {
            background: #ffebee;
            color: #c62828;
        }
    </style>
</head>

<body>
    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-home"></i></div>
                <div class="logo-text">URBAN COOP</div>
            </div>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a class="nav-link active" onclick="showSection('info')" id="info-nav">
                        <i class="fas fa-chart-line nav-icon"></i>
                        Dashboard
                    </a>
                </li>

                <li class="nav-accordion-item">
                    <div class="nav-accordion-header" onclick="toggleAccordion(this)">
                        <div>
                            <i class="fas fa-receipt nav-icon"></i>
                            Pagos
                            <span class="badge"><?= count($pending_payments) ?></span>
                        </div>
                        <i class="fas fa-chevron-down accordion-arrow"></i>
                    </div>
                    <div class="nav-accordion-content">
                        <a class="nav-sub-link" onclick="showSection('payments')">Pagos Pendientes</a>
                        <a class="nav-sub-link" onclick="showSection('payments-late')">Pagos Atrasados</a>
                        <a class="nav-sub-link" onclick="showSection('payments-remuneration')">Remuneración de Horas</a>
                    </div>
                </li>

                <li class="nav-accordion-item">
                    <div class="nav-accordion-header" onclick="toggleAccordion(this)">
                        <div>
                            <i class="fas fa-clock nav-icon"></i>
                            Horas
                            <span class="badge"><?= count($pending_hours) ?></span>
                        </div>
                        <i class="fas fa-chevron-down accordion-arrow"></i>
                    </div>
                    <div class="nav-accordion-content">
                        <a class="nav-sub-link" onclick="showSection('hours')">Horas Pendientes</a>
                        <a class="nav-sub-link" onclick="showSection('hours-history')">Historial de Horas</a>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link" onclick="showSection('users')" id="users-nav">
                        <i class="fas fa-user-plus nav-icon"></i>
                        Trabajadores en Espera
                        <span class="badge"><?= count($pending_users) ?></span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" onclick="showSection('meetings')" id="meetings-nav">
                        <i class="fas fa-calendar-alt nav-icon"></i>
                        Gestionar Reuniones
                    </a>
                </li>

                <li class="nav-accordion-item">
                    <div class="nav-accordion-header" onclick="toggleAccordion(this)">
                        <div>
                            <i class="fas fa-building nav-icon"></i>
                            Unidades
                        </div>
                        <i class="fas fa-chevron-down accordion-arrow"></i>
                    </div>
                    <div class="nav-accordion-content">
                        <a class="nav-sub-link" onclick="showSection('units-assign')">Asignar Unidades</a>
                        <a class="nav-sub-link" onclick="showSection('units-create')">Crear Nueva Unidad</a>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link" onclick="showSection('debug')" id="debug-nav">
                        <i class="fas fa-bug nav-icon"></i>
                        Información Debug
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="header">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 id="page-title">Dashboard</h1>
                </div>
                <div class="header-actions">
                    <div class="user-menu">
                        <i class="fas fa-user-circle"></i>
                        <span>Admin</span>
                    </div>
                </div>
            </header>

            <div class="content">
                <div class="alert alert-success" id="success-alert"></div>
                <div class="alert alert-error" id="error-alert"></div>

                <!-- Info/Dashboard Section -->
                <div class="section active" id="info-section">
                    <div class="stats-grid">
                        <div class="glass-card">
                            <div class="glass-card-icon">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <div class="glass-card-number"><?= count($pending_users) ?></div>
                            <div class="glass-card-label">Usuarios Pendientes</div>
                        </div>

                        <div class="glass-card">
                            <div class="glass-card-icon">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div class="glass-card-number"><?= count($pending_payments) ?></div>
                            <div class="glass-card-label">Comprobantes Pendientes</div>
                        </div>

                        <div class="glass-card">
                            <div class="glass-card-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="glass-card-number"><?= count($pending_hours) ?></div>
                            <div class="glass-card-label">Horas por Aprobar</div>
                        </div>

                        <div class="glass-card">
                            <div class="glass-card-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="glass-card-number"><?= count($upcoming_meetings) ?></div>
                            <div class="glass-card-label">Reuniones Próximas</div>
                        </div>
                    </div>

                    <h3 style="margin-bottom: 20px; color: #333;">
                        <i class="fas fa-calendar-alt"></i> Próximas Reuniones
                    </h3>

                    <?php if (empty($upcoming_meetings)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar"></i>
                            <h3>No hay reuniones programadas</h3>
                            <p>Crea una nueva reunión desde la sección correspondiente</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming_meetings as $meeting): ?>
                            <div class="meeting-card">
                                <div class="meeting-title"><?= htmlspecialchars($meeting['titulo']) ?></div>
                                <?php if ($meeting['descripcion']): ?>
                                    <p style="color: #666; margin: 10px 0;"><?= htmlspecialchars($meeting['descripcion']) ?></p>
                                <?php endif; ?>
                                <div class="meeting-info">
                                    <div class="meeting-info-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?= formatDate($meeting['fecha_reunion']) ?></span>
                                    </div>
                                    <?php if ($meeting['lugar']): ?>
                                        <div class="meeting-info-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?= htmlspecialchars($meeting['lugar']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Users Section (continúa igual) -->
                <div class="section" id="users-section">
                    <?php if (empty($pending_users)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-check"></i>
                            <h3>No hay trabajadores pendientes</h3>
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
                                            </div>
                                        </div>
                                    </div>
                                    <div class="task-actions">
                                        <button class="btn btn-approve" onclick="processUser(<?= $user['id'] ?>, 'approve', this)">
                                            <i class="fas fa-check"></i> Aceptar
                                        </button>
                                        <button class="btn btn-reject" onclick="processUser(<?= $user['id'] ?>, 'reject', this)">
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
                        </div>
                    <?php else: ?>
                        <div class="task-list">
                            <?php foreach ($pending_payments as $payment): ?>
                                <div class="task-card" id="payment-card-<?= $payment['id'] ?>">
                                    <div class="task-header">
                                        <div class="task-info">
                                            <div class="task-title">Comprobante #<?= $payment['id'] ?> - <?= htmlspecialchars($payment['nombre'] . ' ' . $payment['apellido']) ?></div>
                                            <div class="task-details">
                                                <div class="task-detail">
                                                    <i class="fas fa-calendar"></i>
                                                    <span><?= htmlspecialchars($payment['payment_month']) ?>/<?= htmlspecialchars($payment['payment_year']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="task-actions">
                                        <button class="btn btn-approve" onclick="processPayment(<?= $payment['id'] ?>, 'approve', this)">
                                            <i class="fas fa-check"></i> Aprobar
                                        </button>
                                        <button class="btn btn-reject" onclick="processPayment(<?= $payment['id'] ?>, 'reject', this)">
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
                        </div>
                    <?php else: ?>
                        <div class="task-list">
                            <?php foreach ($pending_hours as $hours): ?>
                                <div class="task-card" id="hours-card-<?= $hours['id'] ?>">
                                    <div class="task-header">
                                        <div class="task-info">
                                            <div class="task-title">Registro #<?= $hours['id'] ?> - <?= htmlspecialchars($hours['nombre'] . ' ' . $hours['apellido']) ?></div>
                                            <div class="task-details">
                                                <div class="task-detail">
                                                    <i class="fas fa-clock"></i>
                                                    <span><?= $hours['hours_worked'] ?> horas</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="task-actions">
                                        <button class="btn btn-approve" onclick="processHours(<?= $hours['id'] ?>, 'approve', this)">
                                            <i class="fas fa-check"></i> Aprobar
                                        </button>
                                        <button class="btn btn-reject" onclick="processHours(<?= $hours['id'] ?>, 'reject', this)">
                                            <i class="fas fa-times"></i> Rechazar
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Meetings Section -->
                <div class="section" id="meetings-section">
                    <h3 style="margin-bottom: 20px;">
                        <i class="fas fa-calendar-plus"></i> Crear Nueva Reunión
                    </h3>
                    <form id="meeting-form" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 30px;">
                        <div class="form-group">
                            <label class="form-label">Título de la Reunión *</label>
                            <input type="text" class="form-input" name="titulo" required placeholder="Ej: Reunión de Vecinos Mensual">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-input form-textarea" name="descripcion" placeholder="Detalles sobre los temas a tratar..."></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">Fecha y Hora *</label>
                                <input type="datetime-local" class="form-input" name="fecha_reunion" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Lugar</label>
                                <input type="text" class="form-input" name="lugar" placeholder="Salón de eventos, Área común, etc.">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-approve" style="width: 100%; padding: 15px; font-size: 16px;">
                            <i class="fas fa-plus-circle"></i> Crear Reunión
                        </button>
                    </form>

                    <h3 style="margin-bottom: 20px;">
                        <i class="fas fa-list"></i> Reuniones Programadas
                    </h3>
                    
                    <?php if (empty($upcoming_meetings)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar"></i>
                            <h3>No hay reuniones programadas</h3>
                            <p>Crea la primera reunión usando el formulario anterior</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming_meetings as $meeting): ?>
                            <div class="meeting-card">
                                <div class="meeting-title"><?= htmlspecialchars($meeting['titulo']) ?></div>
                                <?php if ($meeting['descripcion']): ?>
                                    <p style="color: #666; margin: 10px 0;"><?= htmlspecialchars($meeting['descripcion']) ?></p>
                                <?php endif; ?>
                                <div class="meeting-info">
                                    <div class="meeting-info-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?= formatDate($meeting['fecha_reunion']) ?></span>
                                    </div>
                                    <?php if ($meeting['lugar']): ?>
                                        <div class="meeting-info-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?= htmlspecialchars($meeting['lugar']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="meeting-info-item">
                                        <i class="fas fa-info-circle"></i>
                                        <span class="status-badge status-pending"><?= ucfirst($meeting['estado']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Units Assignment Section -->
                <div class="section" id="units-assign-section">
                    <h3 style="margin-bottom: 20px;">
                        <i class="fas fa-hand-holding-heart"></i> Asignar Unidad a Usuario
                    </h3>
                    <form id="unit-assign-form" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); max-width: 800px; margin: 0 auto;">
                        <div class="form-group">
                            <label class="form-label">Seleccionar Usuario *</label>
                            <select class="form-input form-select" name="user_id" required>
                                <option value="">-- Seleccione un usuario --</option>
                                <?php foreach ($approved_users as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Seleccionar Unidad Disponible *</label>
                            <select class="form-input form-select" name="unidad_id" id="unit-selector" required onchange="showUnitDetails(this.value)">
                                <option value="">-- Seleccione una unidad --</option>
                                <?php foreach ($available_units as $unit): ?>
                                    <option value="<?= $unit['id'] ?>" 
                                            data-cuartos="<?= $unit['cuartos'] ?>"
                                            data-banos="<?= $unit['banos'] ?>"
                                            data-tamano="<?= $unit['tamano'] ?>"
                                            data-capacidad="<?= $unit['capacidad'] ?>"
                                            data-tipo="<?= $unit['tipo_unidad'] ?>"
                                            data-bloque="<?= $unit['bloque'] ?>">
                                        <?= htmlspecialchars($unit['bloque'] ? $unit['bloque'] . ' - ' : '') ?>
                                        Unidad <?= htmlspecialchars($unit['numero_unidad']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Detalles de la unidad seleccionada -->
                        <div id="unit-details" style="display: none; background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h4 style="margin-bottom: 15px; color: #333;">
                                <i class="fas fa-info-circle"></i> Detalles de la Unidad
                            </h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="detail-item">
                                    <i class="fas fa-bed" style="color: #d32f2f;"></i>
                                    <strong>Cuartos:</strong> <span id="detail-cuartos"></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-bath" style="color: #d32f2f;"></i>
                                    <strong>Baños:</strong> <span id="detail-banos"></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-ruler-combined" style="color: #d32f2f;"></i>
                                    <strong>Tamaño:</strong> <span id="detail-tamano"></span> m²
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-users" style="color: #d32f2f;"></i>
                                    <strong>Capacidad:</strong> <span id="detail-capacidad"></span> personas
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-home" style="color: #d32f2f;"></i>
                                    <strong>Tipo:</strong> <span id="detail-tipo"></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-building" style="color: #d32f2f;"></i>
                                    <strong>Bloque:</strong> <span id="detail-bloque"></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Fecha de Asignación *</label>
                            <input type="date" class="form-input" name="fecha_asignacion" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Notas Adicionales</label>
                            <textarea class="form-input form-textarea" name="notas" placeholder="Información adicional sobre la asignación..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-approve" style="width: 100%; padding: 15px; font-size: 16px;">
                            <i class="fas fa-check-circle"></i> Asignar Unidad
                        </button>
                    </form>
                </div>

                <!-- Units Create Section -->
                <div class="section" id="units-create-section">
                    <div style="max-width: 900px; margin: 0 auto;">
                        <h3 style="margin-bottom: 20px;">
                            <i class="fas fa-plus-circle"></i> Crear Nueva Unidad
                        </h3>
                        <form id="unit-create-form" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 30px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label class="form-label">Número de Unidad *</label>
                                    <input type="text" class="form-input" name="numero_unidad" required placeholder="Ej: 101, A-5">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Bloque</label>
                                    <input type="text" class="form-input" name="bloque" placeholder="Ej: A, Torre 1">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label class="form-label">Piso *</label>
                                    <input type="number" class="form-input" name="piso" required min="0" placeholder="0">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Cuartos *</label>
                                    <input type="number" class="form-input" name="cuartos" required min="1" placeholder="2">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Baños *</label>
                                    <input type="number" class="form-input" name="banos" required min="1" placeholder="1">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label class="form-label">Tamaño (m²) *</label>
                                    <input type="number" class="form-input" name="tamano" required step="0.01" min="0" placeholder="50.00">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Capacidad *</label>
                                    <select class="form-input form-select" name="capacidad" required>
                                        <option value="">-- Seleccionar --</option>
                                        <option value="2">2 personas</option>
                                        <option value="4">4 personas</option>
                                        <option value="6">6 personas</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Tipo de Unidad *</label>
                                <select class="form-input form-select" name="tipo_unidad" required>
                                    <option value="apartamento">Apartamento</option>
                                    <option value="casa">Casa</option>
                                    <option value="local">Local Comercial</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-approve" style="width: 100%; padding: 15px; font-size: 16px;">
                                <i class="fas fa-save"></i> Crear Unidad
                            </button>
                        </form>

                        <!-- Lista de Unidades Creadas -->
                        <div>
                            <h4 style="margin-bottom: 15px; color: #333;">
                                <i class="fas fa-list"></i> Unidades Registradas (<?= count($all_units) ?>)
                            </h4>
                            <div style="max-height: 500px; overflow-y: auto;">
                                <?php if (empty($all_units)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-building"></i>
                                        <h3>No hay unidades registradas</h3>
                                        <p>Crea la primera unidad usando el formulario anterior</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($all_units as $unit): ?>
                                        <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid <?= $unit['estado'] == 'disponible' ? '#4caf50' : ($unit['estado'] == 'ocupada' ? '#d32f2f' : '#ff9800') ?>;">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <div>
                                                    <strong style="font-size: 16px;">
                                                        <?= htmlspecialchars($unit['bloque'] ? $unit['bloque'] . ' - ' : '') ?>
                                                        Unidad <?= htmlspecialchars($unit['numero_unidad']) ?>
                                                    </strong>
                                                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                                        <i class="fas fa-bed"></i> <?= $unit['cuartos'] ?> cuartos
                                                        | <i class="fas fa-bath"></i> <?= $unit['banos'] ?> baños
                                                        | <i class="fas fa-ruler-combined"></i> <?= $unit['tamano'] ?> m²
                                                        | <i class="fas fa-users"></i> <?= $unit['capacidad'] ?> personas
                                                    </div>
                                                </div>
                                                <span class="status-badge status-<?= $unit['estado'] == 'disponible' ? 'approved' : 'rejected' ?>">
                                                    <?= ucfirst($unit['estado']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payments Late Section -->
                <div class="section" id="payments-late-section">
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Pagos Atrasados</h3>
                        <p>Esta sección mostrará los pagos con retraso</p>
                    </div>
                </div>

                <!-- Payments Remuneration Section -->
                <div class="section" id="payments-remuneration-section">
                    <div class="empty-state">
                        <i class="fas fa-dollar-sign"></i>
                        <h3>Remuneración de Horas</h3>
                        <p>Aquí se calcularán los pagos por horas trabajadas</p>
                    </div>
                </div>

                <!-- Hours History Section -->
                <div class="section" id="hours-history-section">
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h3>Historial de Horas</h3>
                        <p>Aquí se mostrará el historial completo de horas trabajadas</p>
                    </div>
                </div>

                <!-- Debug Section -->
                <div class="section" id="debug-section">
                    <div class="debug-info">
                        <div class="debug-title"><i class="fas fa-info-circle"></i> Información de Debug</div>
                        <p><strong>Estado de conexión:</strong> <span id="connection-status">Conectado</span></p>
                        <p><strong>Total usuarios:</strong> <?= count($all_users) ?></p>
                        <p><strong>Usuarios pendientes:</strong> <?= count($pending_users) ?></p>
                        <p><strong>Comprobantes pendientes:</strong> <?= count($pending_payments) ?></p>
                        <p><strong>Horas pendientes:</strong> <?= count($pending_hours) ?></p>
                        <p><strong>Reuniones programadas:</strong> <?= count($upcoming_meetings) ?></p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Botón flotante perfil -->
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
        }

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
        let currentSection = 'info';

        function showSection(sectionName) {
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });

            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });

            document.querySelectorAll('.nav-sub-link').forEach(link => {
                link.classList.remove('active');
            });

            const section = document.getElementById(sectionName + '-section');
            if (section) {
                section.classList.add('active');
            }

            const navLink = document.getElementById(sectionName + '-nav');
            if (navLink) {
                navLink.classList.add('active');
            }

            const titles = {
                'info': 'Dashboard',
                'users': 'Trabajadores en Espera',
                'payments': 'Pagos Pendientes',
                'payments-late': 'Pagos Atrasados',
                'payments-remuneration': 'Remuneración de Horas',
                'hours': 'Horas Pendientes',
                'hours-history': 'Historial de Horas',
                'meetings': 'Gestionar Reuniones',
                'units-assign': 'Asignar Unidades',
                'units-create': 'Crear Nueva Unidad',
                'debug': 'Información Debug'
            };

            const titleElement = document.getElementById('page-title');
            if (titleElement && titles[sectionName]) {
                titleElement.textContent = titles[sectionName];
            }

            currentSection = sectionName;

            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('open');
            }
        }

        function toggleAccordion(header) {
            const content = header.nextElementSibling;
            const arrow = header.querySelector('.accordion-arrow');
            const isOpen = content.classList.contains('open');

            document.querySelectorAll('.nav-accordion-content').forEach(c => {
                c.classList.remove('open');
            });

            document.querySelectorAll('.accordion-arrow').forEach(a => {
                a.classList.remove('rotated');
            });

            document.querySelectorAll('.nav-accordion-header').forEach(h => {
                h.classList.remove('active');
            });

            if (!isOpen) {
                content.classList.add('open');
                arrow.classList.add('rotated');
                header.classList.add('active');
            }
        }

        function showAlert(message, type) {
            const alert = document.getElementById(type + '-alert');
            if (alert) {
                alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
                alert.style.display = 'block';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);
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
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
                            card.remove();
                            location.reload();
                        }, 300);
                    }
                } else {
                    showAlert(data.message || 'Error al procesar usuario', 'error');
                }
            })
            .catch(error => {
                removeLoading();
                showAlert('Error de conexión', 'error');
            });
        }

        function processPayment(paymentId, action, button) {
            const removeLoading = addLoadingState(button);
            const actionText = action === 'approve' ? 'approve_payment' : 'reject_payment';

            fetch('admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
                            card.remove();
                            location.reload();
                        }, 300);
                    }
                } else {
                    showAlert(data.message || 'Error al procesar comprobante', 'error');
                }
            })
            .catch(error => {
                removeLoading();
                showAlert('Error de conexión', 'error');
            });
        }

        function processHours(hoursId, action, button) {
            const removeLoading = addLoadingState(button);
            const actionText = action === 'approve' ? 'approve_hours' : 'reject_hours';

            fetch('admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
                            card.remove();
                            location.reload();
                        }, 300);
                    }
                } else {
                    showAlert(data.message || 'Error al procesar horas', 'error');
                }
            })
            .catch(error => {
                removeLoading();
                showAlert('Error de conexión', 'error');
            });
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

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function goToProfile() {
            const userData = sessionStorage.getItem('user_data');
            if (!userData) {
                alert('Error: No se encontró información de sesión');
                document.location = '../loginLP.php';
                return;
            }
            window.location.href = '../perfil.php';
        }

        // Meeting Form Handler
        document.getElementById('meeting-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create_meeting');

            fetch('admin.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    this.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Error al crear reunión', 'error');
                console.error('Error:', error);
            });
        });

        // Unit Assignment Form Handler
        document.getElementById('unit-assign-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'assign_unit');

            fetch('admin.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    this.reset();
                    document.getElementById('unit-details').style.display = 'none';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Error al asignar unidad', 'error');
                console.error('Error:', error);
            });
        });

        // Unit Creation Form Handler
        document.getElementById('unit-create-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create_unit');

            fetch('admin.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    this.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Error al crear unidad', 'error');
                console.error('Error:', error);
            });
        });

        // Show Unit Details
        function showUnitDetails(unitId) {
            const select = document.getElementById('unit-selector');
            const selectedOption = select.options[select.selectedIndex];
            const detailsDiv = document.getElementById('unit-details');

            if (unitId && selectedOption) {
                const cuartos = selectedOption.getAttribute('data-cuartos');
                const banos = selectedOption.getAttribute('data-banos');
                const tamano = selectedOption.getAttribute('data-tamano');
                const capacidad = selectedOption.getAttribute('data-capacidad');
                const tipo = selectedOption.getAttribute('data-tipo');
                const bloque = selectedOption.getAttribute('data-bloque');

                document.getElementById('detail-cuartos').textContent = cuartos;
                document.getElementById('detail-banos').textContent = banos;
                document.getElementById('detail-tamano').textContent = tamano;
                document.getElementById('detail-capacidad').textContent = capacidad;
                document.getElementById('detail-tipo').textContent = tipo.charAt(0).toUpperCase() + tipo.slice(1);
                document.getElementById('detail-bloque').textContent = bloque || 'N/A';

                detailsDiv.style.display = 'block';
            } else {
                detailsDiv.style.display = 'none';
            }
        }

        // Session Check
        document.addEventListener('DOMContentLoaded', function () {
            const userData = sessionStorage.getItem('user_data');
            if (!userData) {
                alert('Debes iniciar sesión');
                document.location = 'loginLP.php';
                return;
            }

            const user = JSON.parse(userData);
            if (user.is_admin != 1) {
                alert('No tienes permisos de administrador');
                document.location = 'perfil.php';
                return;
            }

            const userMenu = document.querySelector('.user-menu span');
            if (userMenu) {
                userMenu.textContent = user.name + ' ' + user.surname;
            }

            // Show info section by default
            showSection('info');
        });
    </script>
</body>
</html>