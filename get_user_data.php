<?php
// get_user_data.php - Endpoint para obtener datos del usuario
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de base de datos
$host = 'localhost';
$dbname = 'usuarios_urban_coop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit();
}

// Manejar OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obtener datos JSON o GET
$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
    exit();
}

try {
    // Obtener información del usuario
    $user_stmt = $pdo->prepare("SELECT id, usr_name, usr_surname, usr_email, usr_ci, usr_phone, is_admin, estado FROM usuario WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    // Obtener comprobantes de pago
    $payments_stmt = $pdo->prepare("
        SELECT id, payment_month, payment_year, file_name, file_path, file_size, 
               file_type, description, status, created_at 
        FROM comprobantes_pago 
        WHERE user_id = ? 
        ORDER BY payment_year DESC, payment_month DESC
    ");
    $payments_stmt->execute([$user_id]);
    $payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener registros de horas
    $hours_stmt = $pdo->prepare("
        SELECT id, work_date, hours_worked, description, work_type, created_at 
        FROM horas_trabajadas 
        WHERE user_id = ? 
        ORDER BY work_date DESC 
        LIMIT 20
    ");
    $hours_stmt->execute([$user_id]);
    $hours = $hours_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular total de horas del mes actual
    $current_month = date('Y-m');
    $month_stmt = $pdo->prepare("
        SELECT SUM(hours_worked) as total 
        FROM horas_trabajadas 
        WHERE user_id = ? AND DATE_FORMAT(work_date, '%Y-%m') = ?
    ");
    $month_stmt->execute([$user_id, $current_month]);
    $month_result = $month_stmt->fetch(PDO::FETCH_ASSOC);
    $total_hours_month = $month_result['total'] ?? 0;
    
    // Función para formatear fechas en español
    function formatDateSpanish($date) {
        $months = [
            '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
            '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
            '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
        ];
        
        $day = date('d', strtotime($date));
        $month = $months[date('m', strtotime($date))];
        $year = date('Y', strtotime($date));
        
        return "$day de $month, $year";
    }
    
    function getMonthName($month) {
        $months = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        ];
        return $months[$month] ?? 'Desconocido';
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
    
    // Formatear datos para el frontend
    $formatted_payments = [];
    foreach ($payments as $payment) {
        $formatted_payments[] = [
            'id' => $payment['id'],
            'month' => $payment['payment_month'],
            'year' => $payment['payment_year'],
            'month_name' => getMonthName($payment['payment_month']),
            'file_name' => $payment['file_name'],
            'file_path' => $payment['file_path'],
            'file_size' => formatFileSize($payment['file_size']),
            'file_type' => $payment['file_type'],
            'description' => $payment['description'],
            'status' => $payment['status'],
            'created_at' => date('d/m/Y', strtotime($payment['created_at']))
        ];
    }
    
    $formatted_hours = [];
    foreach ($hours as $hour) {
        $formatted_hours[] = [
            'id' => $hour['id'],
            'work_date' => $hour['work_date'],
            'work_date_formatted' => formatDateSpanish($hour['work_date']),
            'hours_worked' => number_format($hour['hours_worked'], 1),
            'description' => $hour['description'],
            'work_type' => ucfirst($hour['work_type']),
            'created_at' => date('d/m/Y H:i', strtotime($hour['created_at']))
        ];
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['usr_name'],
            'surname' => $user['usr_surname'],
            'email' => $user['usr_email'],
            'ci' => $user['usr_ci'],
            'phone' => $user['usr_phone'],
            'is_admin' => $user['is_admin'],
            'estado' => $user['estado']
        ],
        'payments' => $formatted_payments,
        'hours' => $formatted_hours,
        'total_hours_month' => number_format($total_hours_month, 1),
        'current_month' => date('F Y')
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener datos del usuario: ' . $e->getMessage()
    ]);
}
?>