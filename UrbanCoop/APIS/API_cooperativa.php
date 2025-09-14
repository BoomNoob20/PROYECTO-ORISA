<?php
// API_cooperativa.php - Backend para funcionalidades de comprobantes de pago y horas trabajadas

// Configuración de errores para depuración (eliminar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit();
}

// Verificar y crear tablas si no existen
try {
    // Crear tabla comprobantes_pago si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comprobantes_pago (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            payment_month VARCHAR(2) NOT NULL,
            payment_year VARCHAR(4) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            file_type VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            UNIQUE KEY unique_user_month_year (user_id, payment_month, payment_year)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Crear tabla horas_trabajadas si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS horas_trabajadas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            work_date DATE NOT NULL,
            hours_worked DECIMAL(4,2) NOT NULL,
            description TEXT NOT NULL,
            work_type VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            UNIQUE KEY unique_user_date (user_id, work_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch(PDOException $e) {
    error_log("Error creando tablas: " . $e->getMessage());
}

// Configuración de archivos permitidos
$allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
$allowed_mime_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
$max_file_size = 5 * 1024 * 1024; // 5MB

// Funciones auxiliares
function getMonthName($month) {
    $months = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
    ];
    return $months[$month] ?? 'Desconocido';
}

function formatDateSpanish($date) {
    if (empty($date)) return 'Fecha no válida';
    
    $months = [
        '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
        '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
        '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
    ];
    
    try {
        $day = date('d', strtotime($date));
        $month = $months[date('m', strtotime($date))];
        $year = date('Y', strtotime($date));
        
        return "$day de $month, $year";
    } catch(Exception $e) {
        return date('d/m/Y', strtotime($date));
    }
}

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function validateUser($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT id, usr_name, usr_surname, estado, usr_email, is_admin FROM usuario WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error validando usuario: " . $e->getMessage());
        return false;
    }
}

function createUploadDirectory($path) {
    if (!file_exists($path)) {
        if (!mkdir($path, 0755, true)) {
            throw new Exception("No se pudo crear el directorio de uploads");
        }
    }
    if (!is_writable($path)) {
        throw new Exception("El directorio de uploads no tiene permisos de escritura");
    }
}

// Obtener método de la petición
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Log para depuración
error_log("API llamada: " . $action . " - Método: " . $method);

// Validar que hay una acción definida
if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
    exit();
}

try {
    switch ($action) {
        case 'get_user_data':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit();
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $user_id = intval($input['user_id'] ?? 0);
            
            if ($user_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
                exit();
            }
            
            $user = validateUser($pdo, $user_id);
            if (!$user) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Usuario no válido']);
                exit();
            }
            
            // Obtener comprobantes de pago
            $payments_stmt = $pdo->prepare("
                SELECT id, payment_month, payment_year, file_name, file_path, file_size, 
                       file_type, description, created_at, 'pendiente' as status
                FROM comprobantes_pago 
                WHERE user_id = ? 
                ORDER BY payment_year DESC, payment_month DESC
            ");
            $payments_stmt->execute([$user_id]);
            $payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatear datos de pagos
            foreach ($payments as &$payment) {
                $payment['month_name'] = getMonthName($payment['payment_month']);
                $payment['year'] = $payment['payment_year'];
                $payment['file_size'] = formatFileSize($payment['file_size']);
                $payment['created_at'] = formatDateSpanish($payment['created_at']);
            }
            
            // Obtener horas trabajadas
            $hours_stmt = $pdo->prepare("
                SELECT id, work_date, hours_worked, description, work_type, created_at
                FROM horas_trabajadas 
                WHERE user_id = ? 
                ORDER BY work_date DESC
                LIMIT 50
            ");
            $hours_stmt->execute([$user_id]);
            $hours = $hours_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatear datos de horas
            foreach ($hours as &$record) {
                $record['work_date_formatted'] = formatDateSpanish($record['work_date']);
                $record['created_at'] = formatDateSpanish($record['created_at']);
            }
            
            // Calcular total de horas del mes actual
            $current_month = date('Y-m');
            $total_hours_stmt = $pdo->prepare("
                SELECT COALESCE(SUM(hours_worked), 0) as total 
                FROM horas_trabajadas 
                WHERE user_id = ? AND DATE_FORMAT(work_date, '%Y-%m') = ?
            ");
            $total_hours_stmt->execute([$user_id, $current_month]);
            $total_hours_result = $total_hours_stmt->fetch(PDO::FETCH_ASSOC);
            $total_hours_month = $total_hours_result['total'] ?? 0;
            
            echo json_encode([
                'success' => true,
                'payments' => $payments,
                'hours' => $hours,
                'total_hours_month' => floatval($total_hours_month),
                'current_month' => date('F Y')
            ]);
            break;
            
        case 'upload_payment':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit();
            }
            
            $user_id = intval($_POST['user_id'] ?? 0);
            $payment_month = trim($_POST['payment_month'] ?? '');
            $payment_year = trim($_POST['payment_year'] ?? '');
            $description = trim($_POST['payment_description'] ?? '');
            
            error_log("Upload payment - User ID: $user_id, Month: $payment_month, Year: $payment_year");
            
            if ($user_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
                exit();
            }
            
            $user = validateUser($pdo, $user_id);
            if (!$user) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Usuario no válido']);
                exit();
            }
            
            // Validaciones
            if (empty($payment_month) || empty($payment_year)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El mes y año son obligatorios']);
                exit();
            }
            
            if (!isset($_FILES['payment_file']) || $_FILES['payment_file']['error'] !== UPLOAD_ERR_OK) {
                $error_msg = 'Error al subir el archivo';
                if (isset($_FILES['payment_file']['error'])) {
                    switch ($_FILES['payment_file']['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $error_msg = 'El archivo es demasiado grande';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $error_msg = 'El archivo se subió parcialmente';
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $error_msg = 'No se seleccionó ningún archivo';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $error_msg = 'Falta directorio temporal';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $error_msg = 'Error de permisos al escribir archivo';
                            break;
                    }
                }
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $error_msg]);
                exit();
            }
            
            $file = $_FILES['payment_file'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_type = $file['type'];
            
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validar archivo
            if (!in_array($file_extension, $allowed_extensions)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo se permiten: ' . implode(', ', $allowed_extensions)]);
                exit();
            }
            
            if ($file_size > $max_file_size) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB']);
                exit();
            }
            
            // Verificar si ya existe un comprobante para ese mes y año
            $check_stmt = $pdo->prepare("SELECT id FROM comprobantes_pago WHERE user_id = ? AND payment_month = ? AND payment_year = ?");
            $check_stmt->execute([$user_id, $payment_month, $payment_year]);
            
            if ($check_stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Ya existe un comprobante para ' . getMonthName($payment_month) . ' de ' . $payment_year . '. Elimine el anterior si desea subir uno nuevo.']);
                exit();
            }
            
            // Crear directorio si no existe
            $upload_dir = __DIR__ . "/uploads/payments/";
            createUploadDirectory($upload_dir);
            
            // Generar nombre único para el archivo
            $unique_filename = $user_id . "_" . $payment_year . $payment_month . "_" . time() . "." . $file_extension;
            $file_path = $upload_dir . $unique_filename;
            
            // Mover archivo
            if (move_uploaded_file($file_tmp, $file_path)) {
                // Guardar en base de datos
                $insert_stmt = $pdo->prepare("
                    INSERT INTO comprobantes_pago 
                    (user_id, payment_month, payment_year, file_name, file_path, file_size, file_type, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $insert_stmt->execute([
                    $user_id,
                    $payment_month,
                    $payment_year,
                    $file_name,
                    $file_path,
                    $file_size,
                    $file_type,
                    $description
                ]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Comprobante de ' . getMonthName($payment_month) . ' ' . $payment_year . ' subido exitosamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al subir el archivo. Verifique permisos del directorio.']);
            }
            break;
            
        case 'register_hours':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit();
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $user_id = intval($input['user_id'] ?? 0);
            $work_date = trim($input['work_date'] ?? '');
            $hours_worked = floatval($input['hours_worked'] ?? 0);
            $description = trim($input['description'] ?? '');
            $work_type = trim($input['work_type'] ?? '');
            
            error_log("Register hours - User ID: $user_id, Date: $work_date, Hours: $hours_worked");
            
            if ($user_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
                exit();
            }
            
            $user = validateUser($pdo, $user_id);
            if (!$user) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Usuario no válido']);
                exit();
            }
            
            // Validaciones
            if (empty($work_date)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'La fecha de trabajo es obligatoria']);
                exit();
            }
            
            if ($hours_worked <= 0 || $hours_worked > 24) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Las horas trabajadas deben estar entre 0.5 y 24']);
                exit();
            }
            
            if (empty($description)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'La descripción del trabajo es obligatoria']);
                exit();
            }
            
            if (empty($work_type)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El tipo de trabajo es obligatorio']);
                exit();
            }
            
            if (strtotime($work_date) > time()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'La fecha no puede ser futura']);
                exit();
            }
            
            // Verificar si ya existe un registro para esa fecha
            $check_stmt = $pdo->prepare("SELECT id FROM horas_trabajadas WHERE user_id = ? AND work_date = ?");
            $check_stmt->execute([$user_id, $work_date]);
            
            if ($check_stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Ya existe un registro de horas para esta fecha. Cada fecha solo puede tener un registro.']);
                exit();
            }
            
            // Insertar nuevo registro
            $insert_stmt = $pdo->prepare("INSERT INTO horas_trabajadas (user_id, work_date, hours_worked, description, work_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $insert_stmt->execute([$user_id, $work_date, $hours_worked, $description, $work_type]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Horas registradas exitosamente para el ' . date('d/m/Y', strtotime($work_date))
            ]);
            break;
            
        case 'delete_payment':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit();
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $payment_id = intval($input['payment_id'] ?? 0);
            $user_id = intval($input['user_id'] ?? 0);
            
            if ($payment_id <= 0 || $user_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
                exit();
            }
            
            $user = validateUser($pdo, $user_id);
            if (!$user) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Usuario no válido']);
                exit();
            }
            
            // Verificar que el comprobante pertenece al usuario
            $get_stmt = $pdo->prepare("SELECT file_path FROM comprobantes_pago WHERE id = ? AND user_id = ?");
            $get_stmt->execute([$payment_id, $user_id]);
            $payment = $get_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'No se encontró el comprobante o no tienes permisos para eliminarlo']);
                exit();
            }
            
            // Eliminar de la base de datos
            $delete_stmt = $pdo->prepare("DELETE FROM comprobantes_pago WHERE id = ? AND user_id = ?");
            $delete_stmt->execute([$payment_id, $user_id]);
            
            // Eliminar archivo físico
            if (file_exists($payment['file_path'])) {
                unlink($payment['file_path']);
            }
            
            echo json_encode(['success' => true, 'message' => 'Comprobante eliminado exitosamente']);
            break;
            
        case 'delete_hours':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit();
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $hours_id = intval($input['hours_id'] ?? 0);
            $user_id = intval($input['user_id'] ?? 0);
            
            if ($hours_id <= 0 || $user_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
                exit();
            }
            
            $user = validateUser($pdo, $user_id);
            if (!$user) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Usuario no válido']);
                exit();
            }
            
            $delete_stmt = $pdo->prepare("DELETE FROM horas_trabajadas WHERE id = ? AND user_id = ?");
            $delete_stmt->execute([$hours_id, $user_id]);
            
            if ($delete_stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Registro de horas eliminado exitosamente']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el registro']);
            }
            break;
            
        case 'download_payment':
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit();
            }
            
            $payment_id = intval($_GET['id'] ?? 0);
            $user_id = intval($_GET['user_id'] ?? 0);
            
            if ($payment_id <= 0 || $user_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
                exit();
            }
            
            $user = validateUser($pdo, $user_id);
            if (!$user) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Usuario no válido']);
                exit();
            }
            
            // Obtener información del archivo
            $stmt = $pdo->prepare("SELECT file_path, file_name, file_type FROM comprobantes_pago WHERE id = ? AND user_id = ?");
            $stmt->execute([$payment_id, $user_id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment || !file_exists($payment['file_path'])) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
                exit();
            }
            
            // Configurar headers para descarga
            header('Content-Type: ' . $payment['file_type']);
            header('Content-Disposition: attachment; filename="' . $payment['file_name'] . '"');
            header('Content-Length: ' . filesize($payment['file_path']));
            header('Cache-Control: no-cache, must-revalidate');
            
            // Enviar archivo
            readfile($payment['file_path']);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida: ' . $action]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>