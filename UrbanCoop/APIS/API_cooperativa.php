<?php
// API_cooperativa.php - Fixed version
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response
ini_set('log_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'usuarios_urban_coop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    respondWithError("Database connection error: " . $e->getMessage());
}

// Helper function to send JSON responses
function respondWithSuccess($message, $data = []) {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function respondWithError($message, $data = []) {
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Get action from query parameter or POST data
$action = $_GET['action'] ?? ($_POST['action'] ?? null);

if (!$action) {
    respondWithError("No action specified");
}

// Route to appropriate handler
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
    default:
        respondWithError("Invalid action: " . $action);
}

function handleGetUserData($pdo) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['user_id'])) {
        respondWithError("User ID required");
    }
    
    $user_id = intval($input['user_id']);
    
    if ($user_id <= 0) {
        respondWithError("Invalid user ID");
    }
    
    try {
        // Get user payments
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
                   DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i') as created_at,
                   ROUND(p.file_size / 1024, 2) as file_size_kb
            FROM payment_receipts p 
            WHERE p.user_id = ? 
            ORDER BY p.payment_year DESC, p.payment_month DESC, p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format file sizes
        foreach ($payments as &$payment) {
            if ($payment['file_size_kb'] > 1024) {
                $payment['file_size'] = round($payment['file_size_kb'] / 1024, 2) . ' MB';
            } else {
                $payment['file_size'] = $payment['file_size_kb'] . ' KB';
            }
        }
        
        // Get user hours
        $stmt = $pdo->prepare("
            SELECT h.*, 
                   DATE_FORMAT(h.work_date, '%d/%m/%Y') as work_date_formatted,
                   DATE_FORMAT(h.created_at, '%d/%m/%Y %H:%i') as created_at,
                   CASE h.work_type
                       WHEN 'desarrollo' THEN 'Desarrollo'
                       WHEN 'reunion' THEN 'Reuniones'
                       WHEN 'documentacion' THEN 'Documentación'
                       WHEN 'testing' THEN 'Testing'
                       WHEN 'administrativo' THEN 'Administrativo'
                       WHEN 'soporte' THEN 'Soporte Técnico'
                       WHEN 'investigacion' THEN 'Investigación'
                       WHEN 'otros' THEN 'Otros'
                       ELSE h.work_type
                   END as work_type
            FROM work_hours h 
            WHERE h.user_id = ? 
            ORDER BY h.work_date DESC, h.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total hours for current month
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(hours_worked), 0) as total_hours
            FROM work_hours 
            WHERE user_id = ? 
            AND YEAR(work_date) = YEAR(CURDATE()) 
            AND MONTH(work_date) = MONTH(CURDATE())
        ");
        $stmt->execute([$user_id]);
        $totalHoursResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalHoursMonth = $totalHoursResult['total_hours'] ?? 0;
        
        // Get current month name
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
        
        respondWithSuccess("Data loaded successfully", [
            'payments' => $payments,
            'hours' => $hours,
            'total_hours_month' => $totalHoursMonth,
            'current_month' => $currentMonth
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in handleGetUserData: " . $e->getMessage());
        respondWithError("Error loading user data");
    }
}

function handleUploadPayment($pdo) {
    if (!isset($_POST['user_id']) || !isset($_FILES['payment_file'])) {
        respondWithError("Missing required fields");
    }
    
    $user_id = intval($_POST['user_id']);
    $payment_month = $_POST['payment_month'] ?? '';
    $payment_year = $_POST['payment_year'] ?? '';
    $description = $_POST['payment_description'] ?? '';
    
    if ($user_id <= 0 || empty($payment_month) || empty($payment_year)) {
        respondWithError("Invalid input data");
    }
    
    $file = $_FILES['payment_file'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        respondWithError("Error uploading file");
    }
    
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowedTypes)) {
        respondWithError("File type not allowed. Only PDF, JPG, and PNG files are accepted");
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        respondWithError("File too large. Maximum size is 5MB");
    }
    
    try {
        // Check if payment for this month/year already exists
        $stmt = $pdo->prepare("
            SELECT id FROM payment_receipts 
            WHERE user_id = ? AND payment_month = ? AND payment_year = ?
        ");
        $stmt->execute([$user_id, $payment_month, $payment_year]);
        
        if ($stmt->fetch()) {
            respondWithError("A payment receipt for this month/year already exists");
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = 'uploads/payments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'payment_' . $user_id . '_' . $payment_year . '_' . $payment_month . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            respondWithError("Error saving file");
        }
        
        // Save to database
        $stmt = $pdo->prepare("
            INSERT INTO payment_receipts (user_id, payment_month, payment_year, file_path, file_name, file_type, file_size, description, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $payment_month,
            $payment_year,
            $filepath,
            $file['name'],
            $file['type'],
            $file['size'],
            $description
        ]);
        
        respondWithSuccess("Payment receipt uploaded successfully");
        
    } catch (PDOException $e) {
        error_log("Database error in handleUploadPayment: " . $e->getMessage());
        respondWithError("Error saving payment receipt");
    }
}

function handleRegisterHours($pdo) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['user_id']) || !isset($input['work_date']) || 
        !isset($input['hours_worked']) || !isset($input['description']) || !isset($input['work_type'])) {
        respondWithError("Missing required fields");
    }
    
    $user_id = intval($input['user_id']);
    $work_date = $input['work_date'];
    $hours_worked = floatval($input['hours_worked']);
    $description = trim($input['description']);
    $work_type = $input['work_type'];
    
    // Validate input
    if ($user_id <= 0) {
        respondWithError("Invalid user ID");
    }
    
    if ($hours_worked <= 0 || $hours_worked > 24) {
        respondWithError("Hours worked must be between 0.5 and 24");
    }
    
    if (empty($description)) {
        respondWithError("Description is required");
    }
    
    if (empty($work_type)) {
        respondWithError("Work type is required");
    }
    
    // Validate date
    if (!DateTime::createFromFormat('Y-m-d', $work_date)) {
        respondWithError("Invalid date format");
    }
    
    if ($work_date > date('Y-m-d')) {
        respondWithError("Cannot register hours for future dates");
    }
    
    try {
        // Check if hours for this date already exist
        $stmt = $pdo->prepare("
            SELECT id FROM work_hours 
            WHERE user_id = ? AND work_date = ?
        ");
        $stmt->execute([$user_id, $work_date]);
        
        if ($stmt->fetch()) {
            respondWithError("Hours for this date have already been registered");
        }
        
        // Insert new hours record
        $stmt = $pdo->prepare("
            INSERT INTO work_hours (user_id, work_date, hours_worked, description, work_type, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$user_id, $work_date, $hours_worked, $description, $work_type]);
        
        respondWithSuccess("Hours registered successfully");
        
    } catch (PDOException $e) {
        error_log("Database error in handleRegisterHours: " . $e->getMessage());
        respondWithError("Error registering hours");
    }
}

function handleDeletePayment($pdo) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['payment_id']) || !isset($input['user_id'])) {
        respondWithError("Missing required fields");
    }
    
    $payment_id = intval($input['payment_id']);
    $user_id = intval($input['user_id']);
    
    if ($payment_id <= 0 || $user_id <= 0) {
        respondWithError("Invalid input data");
    }
    
    try {
        // Get file path before deleting
        $stmt = $pdo->prepare("SELECT file_path FROM payment_receipts WHERE id = ? AND user_id = ?");
        $stmt->execute([$payment_id, $user_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            respondWithError("Payment receipt not found");
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM payment_receipts WHERE id = ? AND user_id = ?");
        $stmt->execute([$payment_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            respondWithError("Payment receipt not found or access denied");
        }
        
        // Delete file from filesystem
        if (file_exists($payment['file_path'])) {
            unlink($payment['file_path']);
        }
        
        respondWithSuccess("Payment receipt deleted successfully");
        
    } catch (PDOException $e) {
        error_log("Database error in handleDeletePayment: " . $e->getMessage());
        respondWithError("Error deleting payment receipt");
    }
}

function handleDeleteHours($pdo) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['hours_id']) || !isset($input['user_id'])) {
        respondWithError("Missing required fields");
    }
    
    $hours_id = intval($input['hours_id']);
    $user_id = intval($input['user_id']);
    
    if ($hours_id <= 0 || $user_id <= 0) {
        respondWithError("Invalid input data");
    }
    
    try {
        // Delete hours record
        $stmt = $pdo->prepare("DELETE FROM work_hours WHERE id = ? AND user_id = ?");
        $stmt->execute([$hours_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            respondWithError("Hours record not found or access denied");
        }
        
        respondWithSuccess("Hours record deleted successfully");
        
    } catch (PDOException $e) {
        error_log("Database error in handleDeleteHours: " . $e->getMessage());
        respondWithError("Error deleting hours record");
    }
}

function handleDownloadPayment($pdo) {
    if (!isset($_GET['id']) || !isset($_GET['user_id'])) {
        die("Missing parameters");
    }
    
    $payment_id = intval($_GET['id']);
    $user_id = intval($_GET['user_id']);
    
    if ($payment_id <= 0 || $user_id <= 0) {
        die("Invalid parameters");
    }
    
    try {
        $stmt = $pdo->prepare("SELECT file_path, file_name, file_type FROM payment_receipts WHERE id = ? AND user_id = ?");
        $stmt->execute([$payment_id, $user_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment || !file_exists($payment['file_path'])) {
            die("File not found");
        }
        
        // Set appropriate headers for file download
        header('Content-Type: ' . $payment['file_type']);
        header('Content-Disposition: attachment; filename="' . $payment['file_name'] . '"');
        header('Content-Length: ' . filesize($payment['file_path']));
        header('Cache-Control: private');
        
        // Output file
        readfile($payment['file_path']);
        exit();
        
    } catch (PDOException $e) {
        error_log("Database error in handleDownloadPayment: " . $e->getMessage());
        die("Error accessing file");
    }
}
?>