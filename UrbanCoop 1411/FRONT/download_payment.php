<?php
// download_payment.php - Descarga segura de comprobantes de pago

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
    die("Error de conexión: " . $e->getMessage());
}

// Verificar que se proporcione un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("ID de comprobante inválido");
}

$payment_id = intval($_GET['id']);

try {
    // Obtener información del comprobante
    $stmt = $pdo->prepare("
        SELECT cp.*, u.usr_name, u.usr_surname, u.usr_email 
        FROM comprobantes_pago cp 
        JOIN usuario u ON cp.user_id = u.id 
        WHERE cp.id = ?
    ");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        http_response_code(404);
        die("Comprobante no encontrado");
    }
    
    // Verificar que el archivo existe
    if (!file_exists($payment['file_path'])) {
        http_response_code(404);
        die("Archivo no encontrado en el servidor");
    }
    
    // AQUÍ PUEDES AGREGAR VALIDACIONES DE SEGURIDAD ADICIONALES
    // Por ejemplo, verificar que el usuario actual tenga permisos para descargar este archivo
    
    // Configurar headers para la descarga
    $file_path = $payment['file_path'];
    $file_name = $payment['file_name'];
    $file_size = filesize($file_path);
    $file_type = $payment['file_type'];
    
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers para forzar descarga
    header('Content-Type: ' . $file_type);
    header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // Enviar el archivo
    readfile($file_path);
    
    // Log de descarga (opcional)
    error_log("Comprobante descargado: ID {$payment_id} por usuario {$payment['usr_email']} en " . date('Y-m-d H:i:s'));
    
    exit();
    
} catch(PDOException $e) {
    http_response_code(500);
    die("Error al procesar la descarga: " . $e->getMessage());
}
?>