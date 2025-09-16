<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

define('JWT_SECRET', 'tu_clave_secreta_muy_segura_2024_urbancoop');
define('JWT_ALGORITHM', 'HS256');

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'usuarios_urban_coop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    respondWithError("Error de conexión: " . $e->getMessage());
}

// Funciones JWT
function base64UrlDecode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function verifyJWT($token) {
    if (!$token) return false;
    
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
    
    $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, JWT_SECRET, true);
    $expectedSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    if (!hash_equals($expectedSignature, $signatureEncoded)) {
        return false;
    }
    
    $payload = json_decode(base64UrlDecode($payloadEncoded), true);
    
    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

function getAuthToken() {
    $headers = getallheaders();
    
    // Primero intentar desde headers Authorization
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    
    // Luego intentar desde parámetros POST/GET (para compatibilidad)
    return $_POST['token'] ?? $_GET['token'] ?? null;
}

function requireAuth($pdo) {
    $token = getAuthToken();
    
    if (!$token) {
        respondWithError("Token de autenticación requerido", [], 401);
    }
    
    $payload = verifyJWT($token);
    
    if (!$payload) {
        respondWithError("Token inválido o expirado", [], 401);
    }
    
    // Buscar usuario con campos compatibles
    $stmt = $pdo->prepare("
        SELECT id, estado, is_admin, 
               COALESCE(name, usr_name) as name, 
               COALESCE(surname, usr_surname) as surname,
               COALESCE(email, usr_email) as email
        FROM usuario 
        WHERE id = ?
    ");
    $stmt->execute([$payload['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        respondWithError("Usuario no encontrado", [], 401);
    }
    
    if ($user['estado'] != 2) {
        respondWithError("Usuario no autorizado", [], 401);
    }
    
    return [
        'payload' => $payload,
        'user' => $user
    ];
}

// Funciones de respuesta
function respondWithSuccess($message, $data = []) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function respondWithError($message, $data = [], $httpCode = 400) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Obtener acción
$action = $_GET['action'] ?? ($_POST['action'] ?? null);

if (!$action) {
    respondWithError("Acción no especificada");
}

// Enrutador
switch ($action) {
    case 'get_user_data':
        handleGetUserData($pdo);
        break;
    case 'upload_payment':
        handleUploadPayment($pdo);
        break;
    case 'register_hours':
        handleRegisterHours($pdo);
        break;
    case 'delete_payment':
        handleDeletePayment($pdo);
        break;
    case 'delete_hours':
        handleDeleteHours($pdo);
        break;
    case 'download_payment':
        handleDownloadPayment($pdo);
        break;
    case 'get_payment_summary':
        handleGetPaymentSummary($pdo);
        break;
    case 'add_balance':
        handleAddBalance($pdo);
        break;
    default:
        respondWithError("Acción inválida: " . $action);
}

// Función para obtener datos del usuario
function handleGetUserData($pdo) {
    $auth = requireAuth($pdo);
    $user_id = $auth['payload']['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['user_id']) && $input['user_id'] != $user_id) {
        respondWithError("No autorizado para acceder a datos de otro usuario", [], 403);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, account_balance, monthly_fee, last_balance_reset 
            FROM usuario 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            respondWithError("Usuario no encontrado");
        }
        
        processMonthlyReset($pdo, $user_id, $userData);
        
        $stmt = $pdo->prepare("
            SELECT account_balance, monthly_fee 
            FROM usuario 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $updatedUserData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   CASE p.payment_month 
                       WHEN '01' THEN 'Enero'
                       WHEN '02' THEN 'Febrero'
                       WHEN '03' THEN 'Marzo'
                       WHEN '04' THEN 'Abril'
                       WHEN '05' THEN 'Mayo'
                       WHEN '06' THEN 'Junio'
                       WHEN '07' THEN 'Julio'
                       WHEN '08' THEN 'Agosto'
                       WHEN '09' THEN 'Septiembre'
                       WHEN '10' THEN 'Octubre'
                       WHEN '11' THEN 'Noviembre'
                       WHEN '12' THEN 'Diciembre'
                       ELSE 'Desconocido'
                   END as month_name,
                   DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i') as created_at_formatted,
                   ROUND(p.file_size / 1024, 2) as file_size_kb,
                   FORMAT(p.payment_amount, 2) as payment_amount_formatted
            FROM comprobantes_pago p 
            WHERE p.user_id = ? 
            ORDER BY p.payment_year DESC, p.payment_month DESC, p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($payments as &$payment) {
            if ($payment['file_size_kb'] > 1024) {
                $payment['file_size_display'] = round($payment['file_size_kb'] / 1024, 2) . ' MB';
            } else {
                $payment['file_size_display'] = $payment['file_size_kb'] . ' KB';
            }
        }
        
        $stmt = $pdo->prepare("
            SELECT h.*, 
                   DATE_FORMAT(h.work_date, '%d/%m/%Y') as work_date_formatted,
                   DATE_FORMAT(h.created_at, '%d/%m/%Y %H:%i') as created_at_formatted,
                   CASE h.work_type
                       WHEN 'desarrollo' THEN 'Desarrollo'
                       WHEN 'reunion' THEN 'Reuniones'
                       WHEN 'documentacion' THEN 'Documentación'
                       WHEN 'testing' THEN 'Testing'
                       WHEN 'administrativo' THEN 'Administrativo'
                       WHEN 'soporte' THEN 'Soporte Técnico'
                       WHEN 'investigacion' THEN 'Investigación'
                       WHEN 'mantenimiento' THEN 'Mantenimiento'
                       WHEN 'limpieza' THEN 'Limpieza'
                       WHEN 'reparaciones' THEN 'Reparaciones'
                       WHEN 'eventos' THEN 'Eventos'
                       WHEN 'otros' THEN 'Otros'
                       ELSE h.work_type
                   END as work_type_display
            FROM horas_trabajadas h 
            WHERE h.user_id = ? 
            ORDER BY h.work_date DESC, h.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(hours_worked), 0) as total_hours
            FROM horas_trabajadas 
            WHERE user_id = ? 
            AND YEAR(work_date) = YEAR(CURDATE()) 
            AND MONTH(work_date) = MONTH(CURDATE())
        ");
        $stmt->execute([$user_id]);
        $totalHoursResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalHoursMonth = floatval($totalHoursResult['total_hours'] ?? 0);
        
        $currentBalance = floatval($updatedUserData['account_balance']);
        $monthlyFee = floatval($updatedUserData['monthly_fee']);
        $paymentProgress = min(($currentBalance / $monthlyFee) * 100, 100);
        $paymentStatus = getPaymentStatus($currentBalance, $monthlyFee);
        
        $currentMonth = date('F Y');
        $monthNames = [
            'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
            'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
            'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
            'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
        ];
        
        foreach ($monthNames as $en => $es) {
            $currentMonth = str_replace($en, $es, $currentMonth);
        }
        
        respondWithSuccess("Datos cargados exitosamente", [
            'payments' => $payments,
            'hours' => $hours,
            'total_hours_month' => $totalHoursMonth,
            'current_month' => $currentMonth,
            'payment_info' => [
                'current_balance' => $currentBalance,
                'monthly_fee' => $monthlyFee,
                'payment_progress' => $paymentProgress,
                'payment_status' => $paymentStatus,
                'balance_formatted' => '$' . number_format($currentBalance, 0, ',', '.'),
                'monthly_fee_formatted' => '$' . number_format($monthlyFee, 0, ',', '.')
            ]
        ]);
        
    } catch (PDOException $e) {
        respondWithError("Error al cargar datos del usuario: " . $e->getMessage());
    }
}

function processMonthlyReset($pdo, $user_id, $userData) {
    $currentMonth = date('Y-m-01');
    $lastReset = $userData['last_balance_reset'];
    $currentBalance = floatval($userData['account_balance']);
    $monthlyFee = floatval($userData['monthly_fee']);
    
    if (($lastReset === null || $lastReset < $currentMonth) && $currentBalance >= $monthlyFee) {
        $stmt = $pdo->prepare("
            UPDATE usuario 
            SET account_balance = account_balance - ?, 
                last_balance_reset = ?
            WHERE id = ?
        ");
        $stmt->execute([$monthlyFee, $currentMonth, $user_id]);
    }
}

function getPaymentStatus($balance, $monthlyFee) {
    if ($balance >= $monthlyFee) {
        return 'Al día';
    } elseif ($balance > 0) {
        return 'Pago parcial';
    } else {
        return 'Pendiente';
    }
}

function handleUploadPayment($pdo) {
    $auth = requireAuth($pdo);
    $user_id = $auth['payload']['user_id'];
    
    if (!isset($_FILES['payment_file']) || !isset($_POST['payment_amount'])) {
        respondWithError("Faltan campos requeridos");
    }
    
    $payment_month = $_POST['payment_month'] ?? '';
    $payment_year = $_POST['payment_year'] ?? '';
    $payment_amount = floatval($_POST['payment_amount']);
    $description = $_POST['payment_description'] ?? '';
    
    if (empty($payment_month) || empty($payment_year) || $payment_amount <= 0) {
        respondWithError("Datos de entrada inválidos");
    }
    
    if ($payment_amount < 1000 || $payment_amount > 1000000) {
        respondWithError("El importe debe estar entre $1.000 y $1.000.000");
    }
    
    $file = $_FILES['payment_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        respondWithError("Error al cargar el archivo: " . $file['error']);
    }
    
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);
    
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($detectedType, $allowedTypes)) {
        respondWithError("Tipo de archivo no permitido. Solo se aceptan archivos PDF, JPG y PNG");
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        respondWithError("Archivo muy grande. El tamaño máximo es 5MB");
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM comprobantes_pago 
            WHERE user_id = ? AND payment_month = ? AND payment_year = ?
        ");
        $stmt->execute([$user_id, $payment_month, $payment_year]);
        
        if ($stmt->fetch()) {
            respondWithError("Ya existe un comprobante de pago para este mes/año");
        }
        
        $uploadDir = 'uploads/comprobantes/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                respondWithError("No se pudo crear el directorio de uploads");
            }
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'comprobante_' . $user_id . '_' . $payment_year . '_' . $payment_month . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            respondWithError("Error al guardar el archivo");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO comprobantes_pago (user_id, payment_month, payment_year, file_path, file_name, file_type, file_size, description, payment_amount, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $payment_month,
            $payment_year,
            $filepath,
            $file['name'],
            $detectedType,
            $file['size'],
            $description,
            $payment_amount
        ]);
        
        respondWithSuccess("Comprobante de pago subido exitosamente. Pendiente de aprobación.");
        
    } catch (PDOException $e) {
        if (isset($filepath) && file_exists($filepath)) {
            unlink($filepath);
        }
        respondWithError("Error al guardar el comprobante de pago");
    }
}

function handleRegisterHours($pdo) {
    $auth = requireAuth($pdo);
    $user_id = $auth['payload']['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['work_date']) || 
        !isset($input['hours_worked']) || !isset($input['description']) || !isset($input['work_type'])) {
        respondWithError("Faltan campos requeridos");
    }
    
    $work_date = $input['work_date'];
    $hours_worked = floatval($input['hours_worked']);
    $description = trim($input['description']);
    $work_type = $input['work_type'];
    
    if ($hours_worked <= 0 || $hours_worked > 24) {
        respondWithError("Las horas trabajadas deben estar entre 0.5 y 24");
    }
    
    if (empty($description)) {
        respondWithError("La descripción es requerida");
    }
    
    if (empty($work_type)) {
        respondWithError("El tipo de trabajo es requerido");
    }
    
    if (!DateTime::createFromFormat('Y-m-d', $work_date)) {
        respondWithError("Formato de fecha inválido");
    }
    
    if ($work_date > date('Y-m-d')) {
        respondWithError("No se pueden registrar horas para fechas futuras");
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM horas_trabajadas 
            WHERE user_id = ? AND work_date = ?
        ");
        $stmt->execute([$user_id, $work_date]);
        
        if ($stmt->fetch()) {
            respondWithError("Ya existen horas registradas para esta fecha");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO horas_trabajadas (user_id, work_date, hours_worked, description, work_type, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$user_id, $work_date, $hours_worked, $description, $work_type]);
        
        respondWithSuccess("Horas registradas exitosamente");
        
    } catch (PDOException $e) {
        respondWithError("Error al registrar las horas trabajadas");
    }
}

function handleAddBalance($pdo) {
    $auth = requireAuth($pdo);
    $user_id = $auth['payload']['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['amount'])) {
        respondWithError("Faltan campos requeridos");
    }
    
    $amount = floatval($input['amount']);
    $description = $input['description'] ?? 'Ingreso manual de saldo';
    
    if ($amount <= 0) {
        respondWithError("Datos inválidos");
    }
    
    if ($amount < 100 || $amount > 500000) {
        respondWithError("El monto debe estar entre $100 y $500.000");
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE usuario 
            SET account_balance = account_balance + ? 
            WHERE id = ?
        ");
        $stmt->execute([$amount, $user_id]);
        
        $stmt = $pdo->prepare("SELECT account_balance FROM usuario WHERE id = ?");
        $stmt->execute([$user_id]);
        $newBalance = $stmt->fetchColumn();
        
        respondWithSuccess("Saldo agregado exitosamente", [
            'new_balance' => $newBalance,
            'added_amount' => $amount,
            'balance_formatted' => ' . number_format($newBalance, 0, ',', '.'),
            'added_formatted' => ' . number_format($amount, 0, ',', '.')
        ]);
        
    } catch (PDOException $e) {
        respondWithError("Error al agregar saldo");
    }
}

function handleDeletePayment($pdo) {
    $auth = requireAuth($pdo);
    $user_id = $auth['payload']['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['payment_id'])) {
        respondWithError("Faltan campos requeridos");
    }
    
    $payment_id = intval($input['payment_id']);
    
    if ($payment_id <= 0) {
        respondWithError("Datos inválidos");
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT file_path FROM comprobantes_pago 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$payment_id, $user_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            respondWithError("Comprobante no encontrado");
        }
        
        $stmt = $pdo->prepare("
            DELETE FROM comprobantes_pago 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$payment_id, $user_id]);
        
        if (file_exists($payment['file_path'])) {
            unlink($payment['file_path']);
        }
        
        respondWithSuccess("Comprobante de pago eliminado exitosamente");
        
    } catch (PDOException $e) {
        respondWithError("Error al eliminar el comprobante de pago");
    }
}

function handleDeleteHours($pdo) {
    $auth = requireAuth($pdo);
    $user_id = $auth['payload']['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['hours_id'])) {
        respondWithError("Faltan campos requeridos");
    }
    
    $hours_id = intval($input['hours_id']);
    
    if ($hours_id <= 0) {
        respondWithError("Datos inválidos");
    }
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM horas_trabajadas 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$hours_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            respondWithError("Registro de horas no encontrado");
        }
        
        respondWithSuccess("Registro de horas eliminado exitosamente");
        
    } catch (PDOException $e) {
        respondWithError("Error al eliminar el registro de horas");
    }
}

function handleDownloadPayment($pdo) {
    $auth = requireAuth($pdo);
    $user_id = $auth['payload']['user_id'];
    
    // Aceptar payment_id desde GET o POST
    $payment_id = intval($_GET['payment_id'] ?? $_POST['payment_id'] ?? 0);
    
    if ($payment_id <= 0) {
        respondWithError("Parámetros inválidos");
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT file_path, file_name, file_type 
            FROM comprobantes_pago 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$payment_id, $user_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            respondWithError("Comprobante no encontrado");
        }
        
        $filePath = $payment['file_path'];
        $fileName = $payment['file_name'];
        $fileType = $payment['file_type'];
        
        if (!file_exists($filePath)) {
            respondWithError("Archivo no encontrado en el servidor");
        }
        
        // Limpiar cualquier output previo
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Headers para descarga
        header('Content-Type: ' . $fileType);
        header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        
        // Enviar archivo
        readfile($filePath);
        exit();
        
    } catch (PDOException $e) {
        respondWithError("Error al descargar el archivo");
    }
}

function handleGetPaymentSummary($pdo) {
    $auth = requireAuth($pdo);
    $user_id = $auth['payload']['user_id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                account_balance,
                monthly_fee,
                last_balance_reset,
                (SELECT COALESCE(SUM(payment_amount), 0) 
                 FROM comprobantes_pago 
                 WHERE user_id = ? AND status = 'aprobado' 
                 AND YEAR(created_at) = YEAR(CURDATE()) 
                 AND MONTH(created_at) = MONTH(CURDATE())
                ) as this_month_payments,
                (SELECT COALESCE(SUM(payment_amount), 0) 
                 FROM comprobantes_pago 
                 WHERE user_id = ? AND status = 'pendiente'
                ) as pending_payments
            FROM usuario 
            WHERE id = ?
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$summary) {
            respondWithError("Usuario no encontrado");
        }
        
        $currentBalance = floatval($summary['account_balance']);
        $monthlyFee = floatval($summary['monthly_fee']);
        $thisMonthPayments = floatval($summary['this_month_payments']);
        $pendingPayments = floatval($summary['pending_payments']);
        
        $paymentProgress = min(($currentBalance / $monthlyFee) * 100, 100);
        $paymentStatus = getPaymentStatus($currentBalance, $monthlyFee);
        
        respondWithSuccess("Resumen de pagos obtenido", [
            'current_balance' => $currentBalance,
            'monthly_fee' => $monthlyFee,
            'this_month_payments' => $thisMonthPayments,
            'pending_payments' => $pendingPayments,
            'payment_progress' => $paymentProgress,
            'payment_status' => $paymentStatus,
            'balance_formatted' => ' . number_format($currentBalance, 0, ',', '.'),
            'monthly_fee_formatted' => ' . number_format($monthlyFee, 0, ',', '.'),
            'this_month_formatted' => ' . number_format($thisMonthPayments, 0, ',', '.'),
            'pending_formatted' => ' . number_format($pendingPayments, 0, ',', '.')
        ]);
        
    } catch (PDOException $e) {
        respondWithError("Error al obtener resumen de pagos");
    }
}