<?php
// perfil_api.php - API Simple y Funcional
session_start();
header('Content-Type: application/json');

// Configuración de base de datos
$host = 'localhost';
$dbname = 'usuarios_urban_coop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'Error de conexión BD']));
}

// Obtener parámetros (GET o POST)
$user_id = intval($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
$verify = $_GET['verify'] ?? $_POST['verify'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Validar token
$expected_token = md5('admin_access_' . $user_id . date('Y-m-d'));

if ($user_id <= 0 || $verify !== $expected_token) {
    die(json_encode(['success' => false, 'error' => 'Sesión inválida']));
}

// Verificar que el usuario existe
$stmt = $pdo->prepare("SELECT id, usr_name, usr_surname, usr_email, is_admin, estado FROM usuario WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    die(json_encode(['success' => false, 'error' => 'Usuario no encontrado']));
}

// Procesar acciones
switch ($action) {
    
    // ============================================
    // OBTENER INFO DEL USUARIO
    // ============================================
    case 'get_user_info':
        // Verificar si tiene pago inicial
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comprobantes_pago WHERE user_id = ? AND status = 'approved'");
        $stmt->execute([$user_id]);
        $pagoResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $hasInitialPayment = $pagoResult['count'] > 0;
        
        // Estadísticas de pagos
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_pagos,
                SUM(CASE WHEN status = 'approved' THEN COALESCE(monto, 22000) ELSE 0 END) as monto_aprobado,
                SUM(CASE WHEN status = 'pending' THEN COALESCE(monto, 22000) ELSE 0 END) as monto_pendiente,
                SUM(COALESCE(monto, 22000)) as total_pagado
            FROM comprobantes_pago WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $pagos_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Estadísticas de horas
        $stmt = $pdo->prepare("
            SELECT 
                SUM(hours_worked) as total_horas,
                COUNT(*) as registros
            FROM horas_trabajadas
            WHERE user_id = ? 
            AND MONTH(work_date) = MONTH(CURRENT_DATE())
            AND YEAR(work_date) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute([$user_id]);
        $horas_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $current_user['id'],
                'name' => $current_user['usr_name'] . ' ' . $current_user['usr_surname'],
                'email' => $current_user['usr_email'],
                'is_admin' => (bool)$current_user['is_admin'],
                'estado' => $current_user['estado'],
                'hasInitialPayment' => $hasInitialPayment,
                'hasUnit' => $hasInitialPayment
            ],
            'stats' => [
                'pagos' => [
                    'total' => (int)$pagos_stats['total_pagos'],
                    'aprobado' => (float)($pagos_stats['monto_aprobado'] ?? 0),
                    'pendiente' => (float)($pagos_stats['monto_pendiente'] ?? 0),
                    'total_pagado' => (float)($pagos_stats['total_pagado'] ?? 0)
                ],
                'horas' => [
                    'total' => (float)($horas_stats['total_horas'] ?? 0),
                    'registros' => (int)($horas_stats['registros'] ?? 0)
                ]
            ]
        ]);
        break;
    
    // ============================================
    // VERIFICAR PAGO INICIAL
    // ============================================
    case 'check_initial_payment':
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comprobantes_pago WHERE user_id = ? AND status = 'approved'");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'has_initial_payment' => $result['count'] > 0
        ]);
        break;
    
    // ============================================
    // SUBIR COMPROBANTE
    // ============================================
    case 'upload_payment':
        if (!isset($_FILES['payment_file'])) {
            die(json_encode(['success' => false, 'error' => 'No se recibió archivo']));
        }
        
        $file = $_FILES['payment_file'];
        $month = $_POST['payment_month'] ?? '';
        $year = $_POST['payment_year'] ?? '';
        $description = $_POST['payment_description'] ?? '';
        
        // Validaciones básicas
        if (empty($month) || empty($year)) {
            die(json_encode(['success' => false, 'error' => 'Mes y año requeridos']));
        }
        
        $allowed = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($file['type'], $allowed)) {
            die(json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']));
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            die(json_encode(['success' => false, 'error' => 'Archivo muy grande (máx 5MB)']));
        }
        
        // Verificar duplicados
        $stmt = $pdo->prepare("SELECT id FROM comprobantes_pago WHERE user_id = ? AND payment_month = ? AND payment_year = ?");
        $stmt->execute([$user_id, $month, $year]);
        if ($stmt->fetch()) {
            die(json_encode(['success' => false, 'error' => 'Ya existe comprobante para este mes/año']));
        }
        
        // Crear directorio
        $upload_dir = __DIR__ . '/../../uploads/comprobantes/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Guardar archivo
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = $user_id . "_" . $month . "_" . $year . "_" . time() . "." . $extension;
        $file_path = $upload_dir . $new_filename;
        
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            die(json_encode(['success' => false, 'error' => 'Error al guardar archivo']));
        }
        
        // Guardar en BD
        $stmt = $pdo->prepare("
            INSERT INTO comprobantes_pago 
            (user_id, payment_month, payment_year, file_name, file_path, file_size, file_type, description, monto, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 22000, 'pending')
        ");
        
        $stmt->execute([
            $user_id, $month, $year,
            $file['name'], $file_path,
            $file['size'], $file['type'],
            $description
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Comprobante subido exitosamente',
            'payment_id' => $pdo->lastInsertId()
        ]);
        break;
    
    // ============================================
    // OBTENER PAGOS
    // ============================================
    case 'get_payments':
        $stmt = $pdo->prepare("
            SELECT id, payment_month, payment_year, file_name, file_type, description, monto, status, created_at
            FROM comprobantes_pago
            WHERE user_id = ?
            ORDER BY payment_year DESC, payment_month DESC
        ");
        $stmt->execute([$user_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'payments' => $payments]);
        break;
    
    // ============================================
    // REGISTRAR HORAS
    // ============================================
    case 'register_hours':
        $work_date = $_POST['work_date'] ?? '';
        $hours_worked = floatval($_POST['hours_worked'] ?? 0);
        $description = $_POST['description'] ?? '';
        $work_type = $_POST['work_type'] ?? '';
        
        if (empty($work_date) || $hours_worked <= 0 || empty($description) || empty($work_type)) {
            die(json_encode(['success' => false, 'error' => 'Todos los campos son requeridos']));
        }
        
        if ($hours_worked < 0.5 || $hours_worked > 24) {
            die(json_encode(['success' => false, 'error' => 'Horas deben estar entre 0.5 y 24']));
        }
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO horas_trabajadas (user_id, work_date, hours_worked, description, work_type)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $work_date, $hours_worked, $description, $work_type]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Horas registradas exitosamente',
                'hours_id' => $pdo->lastInsertId()
            ]);
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                die(json_encode(['success' => false, 'error' => 'Ya registraste horas para esta fecha']));
            }
            die(json_encode(['success' => false, 'error' => 'Error al registrar horas']));
        }
        break;
    
    // ============================================
    // OBTENER HORAS
    // ============================================
    case 'get_hours':
        $stmt = $pdo->prepare("
            SELECT id, work_date, hours_worked, description, work_type, created_at
            FROM horas_trabajadas
            WHERE user_id = ?
            ORDER BY work_date DESC
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
        $hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'hours' => $hours]);
        break;
    
    // ============================================
    // ELIMINAR HORAS
    // ============================================
    case 'delete_hours':
        $hours_id = intval($_POST['hours_id'] ?? 0);
        
        if ($hours_id <= 0) {
            die(json_encode(['success' => false, 'error' => 'ID inválido']));
        }
        
        $stmt = $pdo->prepare("DELETE FROM horas_trabajadas WHERE id = ? AND user_id = ?");
        $stmt->execute([$hours_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Registro eliminado']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Registro no encontrado']);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}
?>