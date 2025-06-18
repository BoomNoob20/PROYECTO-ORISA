<?php
session_start();

function connectToDatabase() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ORISA_pruebas";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function handleError($message, $redirect) {
    $_SESSION['error'] = $message;
    header("Location: $redirect");
    exit;
}

$conn = connectToDatabase();
$usuario = $_POST['nombre'];
$apellido = $_POST['Apellido'];
$cedula = $_POST['CI'];
$nroTel = $_POST['Numero de Telefono'];

if (empty($usuario) || empty($apellido) || empty($cedula) || empty($nroTel)) {
    handleError("Por favor, complete todos los campos.", "registro.php");
}

$stmt = $conn->prepare("SELECT id FROM usuario WHERE usr_name = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    handleError("El nombre de usuario ya está en uso.", "registro.php");

} else {
    // Insert the new user into the database
    $insert_stmt = $conn->prepare("INSERT INTO usuario (usr_name, Apellido, CI, `Numero de Telefono`) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("ssss", $usuario, $apellido, $cedula, $nroTel);
    if ($insert_stmt->execute()) {
        $_SESSION['success'] = "Registro exitoso. Ahora puedes iniciar sesión.";
        $insert_stmt->close();
        header("Location: index.php");
        exit;
    } else {
        handleError("Error al registrar el usuario: " . $conn->error, "registro.php");
    }
}

$stmt->close();
$conn->close();
?>


