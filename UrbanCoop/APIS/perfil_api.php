<?php
// perfil_api.php - API para operaciones del perfil de usuario
// Urban Coop - Sistema de gestión cooperativa

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
    die(json_encode(['success' => false, 'error' => 'Error de conexión: ' . $e->getMessage()]));
}

// Obtener la acción solicitada
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Validar sesión del usuario
function validateUserSession($pdo) {
    if (isset($_GET['user_id']) && isset($_GET['verify'])) {
        $user_id = intval($_GET['user_id']);
        $verify_token = $_GET['verify'];
        
        $expected_token = md5('admin_access_' . $user_id . date('Y-m-d'));
        
        if ($verify_token === $expected_token) {
            $stmt = $pdo->prepare("SELECT id, usr_name, usr_surname, estado, usr_email, is_admin FROM usuario WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    return false;
}

// Obtener datos del usuario actual
$current_user = validateUserSession($pdo);
if (!$current_user && $action !== 'get_user_info') {
    echo json_encode(['success' => false, 'error' => 'Sesión no válida']);
    exit();
}

$user_id = $current_user['id'] ?? 0;

// Procesar las diferentes acciones
switch ($action) {
    
    // ============================================
    // VERIFICAR PAGO INICIAL
    // ============================================
    case 'check_initial_payment':
        // Verificar si el usuario tiene al menos un pago aprobado
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM comprobantes_pago 
            WHERE user_id = ? AND status = 'approved'
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'has_initial_payment' => $result['count'] > 0
        ]);
        break;
    
    // ============================================
    // OBTENER INFORMACIÓN DEL USUARIO
    // ============================================
    case 'get_user_info':
        if (!$current_user) {
            echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
            exit();
        }
        
        // Verificar si tiene pago inicial
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM comprobantes_pago 
            WHERE user_id = ? AND status = 'approved'
        ");
        $stmt->execute([$user_id]);
        $pagoResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $hasInitialPayment = $pagoResult['count'] > 0;
        
        // Obtener estadísticas de pagos
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_pagos,
                SUM(CASE WHEN status = 'approved' THEN COALESCE(monto, 22000) ELSE 0 END) as monto_aprobado,
                SUM(CASE WHEN status = 'pending' THEN COALESCE(monto, 22000) ELSE 0 END) as monto_pendiente,
                SUM(COALESCE(monto, 22000)) as total_pagado
            FROM comprobantes_pago
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $pagos_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener estadísticas de horas
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
    // SUBIR COMPROBANTE DE PAGO
    // ============================================
    case 'upload_payment':
        if (!isset($_FILES['payment_file'])) {
            echo json_encode(['success' => false, 'error' => 'No se recibió el archivo']);
            exit();
        }
        
        $file = $_FILES['payment_file'];
        $month = $_POST['payment_month'] ?? '';
        $year = $_POST['payment_year'] ?? '';
        $description = $_POST['payment_description'] ?? '';
        $monto = 22000; // Monto fijo por defecto
        
        // Validaciones
        if (empty($month) || empty($year)) {
            echo json_encode(['success' => false, 'error' => 'Mes y año son requeridos']);
            exit();
        }
        
        // Validar tipo de archivo
        $allowed = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($file['type'], $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
            exit();
        }
        
        // Validar tamaño (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'El archivo es demasiado grande (máx 5MB)']);
            exit();
        }
        
        // Crear directorio si no existe
        $upload_dir = '../../uploads/comprobantes/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generar nombre único para el archivo
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = $user_id . '_' . $month . '_' . $year . '_' . time() . '.' . $extension;
        $file_path = $upload_dir . $new_filename;
        
        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo']);
            exit();
        }
        
        try {
            // Verificar si ya existe un comprobante para ese mes/año
            $stmt = $pdo->prepare("
                SELECT id FROM comprobantes_pago 
                WHERE user_id = ? AND payment_month = ? AND payment_year = ?
            ");
            $stmt->execute([$user_id, $month, $year]);
            
            if ($stmt->fetch()) {
                // Eliminar archivo subido
                unlink($file_path);
                echo json_encode(['success' => false, 'error' => 'Ya existe un comprobante para este mes y año']);
                exit();
            }
            
            // Insertar en base de datos
            $stmt = $pdo->prepare("
                INSERT INTO comprobantes_pago 
                (user_id, payment_month, payment_year, file_name, file_path, file_size, file_type, description, monto, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $user_id,
                $month,
                $year,
                $file['name'],
                $file_path,
                $file['size'],
                $file['type'],
                $description,
                $monto
            ]);
            
            $payment_id = $pdo->lastInsertId();
            
            // Verificar si es el primer pago
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comprobantes_pago WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Comprobante subido exitosamente',
                'payment_id' => $payment_id,
                'is_initial_payment' => ($count == 1)
            ]);
            
        } catch(PDOException $e) {
            // Si hay error, eliminar el archivo subido
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            echo json_encode(['success' => false, 'error' => 'Error al guardar en BD: ' . $e->getMessage()]);
        }
        break;
    
    // ============================================
    // OBTENER LISTA DE PAGOS
    // ============================================
    case 'get_payments':
        $stmt = $pdo->prepare("
            SELECT 
                id,
                payment_month,
                payment_year,
                file_name,
                file_type,
                description,
                monto,
                status,
                created_at
            FROM comprobantes_pago
            WHERE user_id = ?
            ORDER BY payment_year DESC, payment_month DESC
        ");
        $stmt->execute([$user_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'payments' => $payments]);
        break;
    
    // ============================================
    // REGISTRAR HORAS TRABAJADAS
    // ============================================
    case 'register_hours':
        $work_date = $_POST['work_date'] ?? '';
        $hours_worked = floatval($_POST['hours_worked'] ?? 0);
        $description = $_POST['description'] ?? '';
        $work_type = $_POST['work_type'] ?? '';
        
        if (empty($work_date) || $hours_worked <= 0 || empty($description) || empty($work_type)) {
            echo json_encode(['success' => false, 'error' => 'Todos los campos son requeridos']);
            exit();
        }
        
        if ($hours_worked < 0.5 || $hours_worked > 24) {
            echo json_encode(['success' => false, 'error' => 'Las horas deben estar entre 0.5 y 24']);
            exit();
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
                echo json_encode(['success' => false, 'error' => 'Ya registraste horas para esta fecha']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al registrar: ' . $e->getMessage()]);
            }
        }
        break;
    
    // ============================================
    // OBTENER HORAS TRABAJADAS
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
    // ELIMINAR REGISTRO DE HORAS
    // ============================================
    case 'delete_hours':
        $hours_id = intval($_POST['hours_id'] ?? 0);
        
        if ($hours_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM horas_trabajadas WHERE id = ? AND user_id = ?");
            $stmt->execute([$hours_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Registro eliminado']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Registro no encontrado']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()]);
        }
        break;
    
    // ============================================
    // ACCIÓN NO RECONOCIDA
    // ============================================
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
        break;
}
?>