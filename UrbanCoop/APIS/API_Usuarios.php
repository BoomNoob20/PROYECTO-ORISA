<?php
// Capturar errores y enviarlos como JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en HTML

// Buffer de salida para capturar cualquier error
ob_start();

// Función para manejar errores fatales
function handleFatalError() {
    $error = error_get_last();
    if ($error && $error['type'] === E_ERROR) {
        ob_clean(); // Limpiar buffer
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error fatal en el servidor: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
}
register_shutdown_function('handleFatalError');

try {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    // Configuración de la base de datos
    class Database
    {
        private $host = "localhost";
        private $db_name = "usuarios_urban_coop";
        private $username = "root";
        private $password = "";
        public $conn;

        public function getConnection()
        {
            $this->conn = null;
            try {
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
                $this->conn->exec("set names utf8");
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $exception) {
                throw new Exception("Error de conexión a la base de datos: " . $exception->getMessage());
            }
            return $this->conn;
        }
    }

    // Clase para manejar usuarios
    class User
    {
        private $conn;
        private $table_name = "usuario";

        public $id;
        public $name;
        public $surname;
        public $email;
        public $password;
        public $ci;
        public $phone;

        public function __construct($db)
        {
            $this->conn = $db;
        }

        // Crear tabla si no existe
        public function createTable()
        {
            $query = "
                CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    usr_name VARCHAR(100) NOT NULL COMMENT 'Nombre del usuario',
                    usr_surname VARCHAR(100) NOT NULL COMMENT 'Apellido del usuario', 
                    usr_email VARCHAR(100) NOT NULL COMMENT 'Email del usuario',
                    usr_pass VARCHAR(255) NOT NULL COMMENT 'Contraseña',
                    usr_ci VARCHAR(20) NOT NULL COMMENT 'Cédula de identidad',
                    usr_phone VARCHAR(20) NOT NULL COMMENT 'Teléfono',
                    is_admin INT(11) NOT NULL DEFAULT 0 COMMENT '0=Usuario normal, 1=Administrador',
                    estado INT(11) NOT NULL DEFAULT 1 COMMENT '1=Pendiente, 2=Aprobado, 3=Rechazado',
                    PRIMARY KEY (id),
                    UNIQUE KEY usr_email (usr_email),
                    UNIQUE KEY usr_ci (usr_ci),
                    INDEX idx_estado (estado),
                    INDEX idx_admin (is_admin)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                COMMENT='Tabla principal de usuarios del sistema Urban Coop'";

            try {
                $this->conn->exec($query);
                return true;
            } catch (PDOException $exception) {
                throw new Exception("Error al crear tabla: " . $exception->getMessage());
            }
        }

        // Registrar usuario
        public function register()
        {
            // Verificar si el email ya existe
            if ($this->emailExists()) {
                return array("success" => false, "message" => "El email ya está registrado");
            }

            // Verificar si la CI ya existe
            if ($this->ciExists()) {
                return array("success" => false, "message" => "La CI ya está registrada");
            }

            $query = "INSERT INTO " . $this->table_name . " 
                     SET usr_name=:name, usr_surname=:surname, usr_email=:email, usr_pass=:password, usr_ci=:ci, usr_phone=:phone";

            $stmt = $this->conn->prepare($query);

            // Limpiar datos
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->surname = htmlspecialchars(strip_tags($this->surname));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->ci = htmlspecialchars(strip_tags($this->ci));
            $this->phone = htmlspecialchars(strip_tags($this->phone));

            // Hash de la contraseña
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);

            // Bind de valores
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":surname", $this->surname);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $this->password);
            $stmt->bindParam(":ci", $this->ci);
            $stmt->bindParam(":phone", $this->phone);

            try {
                if ($stmt->execute()) {
                    return array("success" => true, "message" => "Usuario registrado exitosamente");
                }
            } catch (PDOException $e) {
                return array("success" => false, "message" => "Error al registrar usuario: " . $e->getMessage());
            }

            return array("success" => false, "message" => "Error al registrar usuario");
        }

        // Login de usuario
        public function login()
        {
            $query = "SELECT * FROM " . $this->table_name . " WHERE usr_email = ?";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->email);
            $stmt->execute();

            $num = $stmt->rowCount();

            if ($num > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verificar contraseña
                if (password_verify($this->password, $row['usr_pass'])) {
                    return array(
                        "success" => true,
                        "message" => "Login exitoso",
                        "user" => array(
                            "id" => $row['id'],
                            "name" => $row['usr_name'],
                            "surname" => $row['usr_surname'],
                            "email" => $row['usr_email'],
                            "ci" => $row['usr_ci'],
                            "phone" => $row['usr_phone'],
                            "is_admin" => $row['is_admin'],
                            "estado" => $row['estado']
                        )
                    );
                } else {
                    return array("success" => false, "message" => "Contraseña incorrecta");
                }
            }

            return array("success" => false, "message" => "Usuario no encontrado");
        }

        // Verificar si el email existe
        private function emailExists()
        {
            $query = "SELECT id FROM " . $this->table_name . " WHERE usr_email = ? LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->email);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        }

        // Verificar si la CI existe
        private function ciExists()
        {
            $query = "SELECT id FROM " . $this->table_name . " WHERE usr_ci = ? LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->ci);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        }
    }

    // Función para validar datos de entrada
    function validateInput($data)
    {
        $errors = array();

        if (empty($data['name'])) {
            $errors[] = "El nombre es requerido";
        }

        if (empty($data['surname'])) {
            $errors[] = "El apellido es requerido";
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email válido es requerido";
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors[] = "La contraseña debe tener al menos 6 caracteres";
        }

        if (empty($data['ci'])) {
            $errors[] = "La CI es requerida";
        }

        if (empty($data['phone'])) {
            $errors[] = "El teléfono es requerido";
        }

        return $errors;
    }

    // Manejar OPTIONS para CORS
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    // Instanciar base de datos y objeto usuario
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    // Crear tabla si no existe
    $user->createTable();

    // Obtener datos JSON
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido recibido: " . json_last_error_msg());
    }

    // Obtener la acción
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action == 'register' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        // REGISTRO DE USUARIO
        
        // Validar que lleguen todos los datos
        if (!isset($data['name']) || !isset($data['surname']) || !isset($data['email']) || 
            !isset($data['password']) || !isset($data['ci']) || !isset($data['phone'])) {
            
            ob_clean(); // Limpiar buffer
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Datos incompletos"]);
            exit();
        }
        
        // Validar datos
        $validation_errors = validateInput($data);
        if (!empty($validation_errors)) {
            ob_clean(); // Limpiar buffer
            http_response_code(400);
            echo json_encode(["success" => false, "message" => implode(", ", $validation_errors)]);
            exit();
        }

        // Asignar datos al objeto usuario
        $user->name = $data['name'];
        $user->surname = $data['surname'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->ci = $data['ci'];
        $user->phone = $data['phone'];

        // Registrar usuario
        $result = $user->register();
        
        ob_clean(); // Limpiar buffer
        echo json_encode($result);

    } elseif ($action == 'login' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        // LOGIN DE USUARIO
        if (empty($data['email']) || empty($data['password'])) {
            ob_clean(); // Limpiar buffer
            echo json_encode(array("success" => false, "message" => "Email y contraseña son requeridos"));
            exit();
        }

        // Asignar datos al objeto usuario
        $user->email = $data['email'];
        $user->password = $data['password'];

        // Hacer login
        $result = $user->login();
        
        ob_clean(); // Limpiar buffer
        echo json_encode($result);

    } else {
        // Ruta no encontrada
        ob_clean(); // Limpiar buffer
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "Endpoint no encontrado. Use ?action=register o ?action=login"));
    }

} catch (Exception $e) {
    ob_clean(); // Limpiar buffer
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
} catch (Error $e) {
    ob_clean(); // Limpiar buffer
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fatal: ' . $e->getMessage()
    ]);
}
?>