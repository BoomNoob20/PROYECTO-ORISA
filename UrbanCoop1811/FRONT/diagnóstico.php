<?php
// diagnostic.php - Script para diagnosticar problemas de autenticación
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico Urban Coop</h1>";

// 1. Verificar conexión a base de datos
echo "<h2>1. Conexión a Base de Datos</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=usuarios_urban_coop;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión exitosa<br>";
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    exit();
}

// 2. Verificar tablas
echo "<h2>2. Verificar Tablas</h2>";
$tables = ['usuario', 'comprobantes_pago', 'horas_trabajadas'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Tabla '$table': {$result['count']} registros<br>";
    } catch(PDOException $e) {
        echo "❌ Error en tabla '$table': " . $e->getMessage() . "<br>";
    }
}

// 3. Verificar usuarios
echo "<h2>3. Usuarios Registrados</h2>";
$stmt = $pdo->query("SELECT id, usr_name, usr_surname, usr_email, is_admin, estado FROM usuario LIMIT 10");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Admin</th><th>Estado</th><th>Token Hoy</th></tr>";
foreach ($users as $user) {
    $token = md5('admin_access_' . $user['id'] . date('Y-m-d'));
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['usr_name']} {$user['usr_surname']}</td>";
    echo "<td>{$user['usr_email']}</td>";
    echo "<td>" . ($user['is_admin'] ? 'Sí' : 'No') . "</td>";
    echo "<td>{$user['estado']}</td>";
    echo "<td style='font-size: 10px;'>$token</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Generar URLs de prueba
echo "<h2>4. URLs de Prueba</h2>";
echo "<p>Usa estas URLs para probar (reemplaza USER_ID con el ID del usuario):</p>";
foreach ($users as $user) {
    $token = md5('admin_access_' . $user['id'] . date('Y-m-d'));
    $url = "perfil.php?user_id={$user['id']}&verify=$token";
    echo "<strong>{$user['usr_name']} {$user['usr_surname']}:</strong><br>";
    echo "<a href='$url' target='_blank'>$url</a><br><br>";
}

// 5. Verificar directorio de uploads
echo "<h2>5. Directorio de Uploads</h2>";
$upload_dir = __DIR__ . '/../../uploads/comprobantes/';
if (file_exists($upload_dir)) {
    if (is_writable($upload_dir)) {
        echo "✅ Directorio existe y es escribible: $upload_dir<br>";
    } else {
        echo "⚠️ Directorio existe pero NO es escribible: $upload_dir<br>";
        echo "<p style='color: red;'>SOLUCIÓN: Ejecuta en terminal:<br><code>chmod -R 777 " . dirname($upload_dir) . "/uploads</code></p>";
    }
} else {
    echo "❌ Directorio NO existe: $upload_dir<br>";
    echo "<p style='color: orange;'>Se creará automáticamente al subir el primer archivo</p>";
}

// 6. Probar validación de sesión
echo "<h2>6. Prueba de Validación de Sesión</h2>";
if (isset($_GET['test_user_id']) && isset($_GET['test_verify'])) {
    $test_id = intval($_GET['test_user_id']);
    $test_token = $_GET['test_verify'];
    $expected_token = md5('admin_access_' . $test_id . date('Y-m-d'));
    
    echo "User ID: $test_id<br>";
    echo "Token recibido: $test_token<br>";
    echo "Token esperado: $expected_token<br>";
    
    if ($test_token === $expected_token) {
        echo "✅ Token válido<br>";
        
        $stmt = $pdo->prepare("SELECT id, usr_name, usr_email FROM usuario WHERE id = ?");
        $stmt->execute([$test_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✅ Usuario encontrado: {$user['usr_name']} ({$user['usr_email']})<br>";
        } else {
            echo "❌ Usuario NO encontrado en BD<br>";
        }
    } else {
        echo "❌ Token inválido<br>";
    }
} else {
    echo "<p>Para probar la validación, agrega a la URL: <code>&test_user_id=2&test_verify=TOKEN</code></p>";
}

// 7. Ver últimos comprobantes
echo "<h2>7. Últimos Comprobantes Subidos</h2>";
$stmt = $pdo->query("
    SELECT cp.*, u.usr_name, u.usr_surname 
    FROM comprobantes_pago cp
    JOIN usuario u ON cp.user_id = u.id
    ORDER BY cp.created_at DESC
    LIMIT 5
");
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($payments) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Usuario</th><th>Mes/Año</th><th>Estado</th><th>Archivo</th><th>Fecha</th></tr>";
    foreach ($payments as $p) {
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['usr_name']} {$p['usr_surname']}</td>";
        echo "<td>{$p['payment_month']}/{$p['payment_year']}</td>";
        echo "<td>{$p['status']}</td>";
        echo "<td>{$p['file_name']}</td>";
        echo "<td>{$p['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay comprobantes subidos</p>";
}

echo "<hr>";
echo "<p><strong>Fecha actual del servidor:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Zona horaria:</strong> " . date_default_timezone_get() . "</p>";
?>