<?php
// test_params.php - Prueba simple de parámetros
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test de Parámetros</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
        pre { background: #333; color: #0f0; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Test de Parámetros Urban Coop</h1>
    
    <h2>1. URL Actual</h2>
    <div class="info">
        <pre><?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></pre>
    </div>
    
    <h2>2. Parámetros GET Recibidos</h2>
    <div class="info">
        <pre><?php print_r($_GET); ?></pre>
    </div>
    
    <h2>3. Validación</h2>
    <?php
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $verify = isset($_GET['verify']) ? $_GET['verify'] : '';
    
    echo "<p><strong>user_id:</strong> " . ($user_id > 0 ? "<span class='success'>$user_id ✓</span>" : "<span class='error'>NO RECIBIDO ✗</span>") . "</p>";
    echo "<p><strong>verify:</strong> " . (!empty($verify) ? "<span class='success'>" . htmlspecialchars($verify) . " ✓</span>" : "<span class='error'>NO RECIBIDO ✗</span>") . "</p>";
    
    if ($user_id > 0 && !empty($verify)) {
        $expected = md5('admin_access_' . $user_id . date('Y-m-d'));
        
        echo "<h3>Validación de Token</h3>";
        echo "<p><strong>Token recibido:</strong> $verify</p>";
        echo "<p><strong>Token esperado:</strong> $expected</p>";
        
        if ($verify === $expected) {
            echo "<p class='success'>✅ TOKEN VÁLIDO - Autenticación correcta</p>";
            
            // Conectar a BD y verificar usuario
            try {
                $pdo = new PDO("mysql:host=localhost;dbname=usuarios_urban_coop;charset=utf8mb4", "root", "");
                $stmt = $pdo->prepare("SELECT usr_name, usr_surname, usr_email FROM usuario WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    echo "<p class='success'>✅ Usuario encontrado en BD:</p>";
                    echo "<ul>";
                    echo "<li><strong>Nombre:</strong> {$user['usr_name']} {$user['usr_surname']}</li>";
                    echo "<li><strong>Email:</strong> {$user['usr_email']}</li>";
                    echo "</ul>";
                } else {
                    echo "<p class='error'>❌ Usuario no existe en la base de datos</p>";
                }
            } catch(Exception $e) {
                echo "<p class='error'>❌ Error de BD: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p class='error'>❌ TOKEN INVÁLIDO</p>";
            echo "<p>El token puede estar expirado (cambia cada día) o ser incorrecto.</p>";
        }
    } else {
        echo "<p class='error'>❌ Faltan parámetros en la URL</p>";
        echo "<p>Ejemplo de URL correcta:</p>";
        echo "<pre>test_params.php?user_id=2&verify=TOKEN_AQUI</pre>";
    }
    ?>
    
    <h2>4. JavaScript Test</h2>
    <div id="jsTest"></div>
    
    <script>
        console.log('=== TEST DE PARÁMETROS JS ===');
        console.log('window.location.search:', window.location.search);
        
        const params = new URLSearchParams(window.location.search);
        const userId = params.get('user_id');
        const verify = params.get('verify');
        
        console.log('user_id extraído:', userId);
        console.log('verify extraído:', verify);
        
        const testDiv = document.getElementById('jsTest');
        testDiv.innerHTML = `
            <div class="info">
                <h3>Extracción con JavaScript:</h3>
                <p><strong>user_id:</strong> ${userId || '<span class="error">NULL</span>'}</p>
                <p><strong>verify:</strong> ${verify || '<span class="error">NULL</span>'}</p>
                <p><strong>window.location.search:</strong> ${window.location.search || '<span class="error">VACÍO</span>'}</p>
            </div>
        `;
    </script>
    
    <hr>
    <p><small>Fecha del servidor: <?php echo date('Y-m-d H:i:s'); ?></small></p>
</body>
</html>