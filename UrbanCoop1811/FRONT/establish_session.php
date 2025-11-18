<?php
// establish_session.php - Manejo de sesión sin JWT
session_start();

// Configurar headers para CORS y JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Cache-Control');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función para responder con JSON
function jsonResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

try {
    // Solo acepta método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Método no permitido');
    }
    
    // Obtener datos del request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        jsonResponse(false, 'Datos JSON inválidos');
    }
    
    // Verificar que la acción sea correcta
    if (!isset($data['action']) || $data['action'] !== 'establish_session') {
        jsonResponse(false, 'Acción no válida');
    }
    
    // Verificar datos requeridos
    $required_fields = ['user_id', 'user_email', 'user_name', 'user_surname'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            jsonResponse(false, "Campo requerido faltante: $field");
        }
    }
    
    // Establecer variables de sesión PHP
    $_SESSION['user_id'] = intval($data['user_id']);
    $_SESSION['user_email'] = $data['user_email'];
    $_SESSION['user_name'] = $data['user_name'];
    $_SESSION['user_surname'] = $data['user_surname'];
    $_SESSION['is_admin'] = intval($data['is_admin'] ?? 0);
    $_SESSION['estado'] = intval($data['estado'] ?? 1);
    $_SESSION['login_time'] = time();
    $_SESSION['session_id'] = session_id();
    
    // Generar token CSRF para admin panel
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Log de la sesión establecida
    error_log("PHP Session established for user ID: " . $_SESSION['user_id'] . 
              " Email: " . $_SESSION['user_email'] . 
              " Is Admin: " . $_SESSION['is_admin']);
    
    // Respuesta exitosa
    jsonResponse(true, 'Sesión PHP establecida correctamente', [
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'],
        'is_admin' => $_SESSION['is_admin'],
        'csrf_token' => $_SESSION['csrf_token']
    ]);
    
} catch (Exception $e) {
    error_log("Error in establish_session.php: " . $e->getMessage());
    jsonResponse(false, 'Error interno del servidor: ' . $e->getMessage());
}
?>