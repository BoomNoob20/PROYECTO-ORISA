// Theme toggle functionality
const themeToggle = document.getElementById('themeToggle');
const body = document.body;

let currentTheme = 'light';

function toggleTheme() {
    currentTheme = currentTheme === 'light' ? 'dark' : 'light';
    body.setAttribute('data-theme', currentTheme);
}

themeToggle.addEventListener('click', toggleTheme);

// Message functions
function showMessage(message, type) {
    const messageArea = document.getElementById('message-area');
    const messageClass = type === 'error' ? 'error-message' : 'success-message';
    
    messageArea.innerHTML = `<div class="${messageClass}">${message}</div>`;
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageArea.innerHTML = '';
    }, 5000);
}

function setButtonLoading(loading) {
    const button = document.getElementById('loginButton');
    if (loading) {
        button.disabled = true;
        button.innerHTML = '<span class="loading"></span>Iniciando sesión...';
    } else {
        button.disabled = false;
        button.innerHTML = 'SIGN IN';
    }
}

// Form functionality
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = e.target;
    const email = form.email.value.trim();
    const password = form.password.value;
    
    // Validaciones básicas
    if (!email || !password) {
        showMessage('Por favor completa todos los campos', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showMessage('Por favor ingresa un email válido', 'error');
        return;
    }
    
    if (password.length < 6) {
        showMessage('La contraseña debe tener al menos 6 caracteres', 'error');
        return;
    }
    
    // Mostrar estado de carga
    setButtonLoading(true);
    
    const data = {
        email: email,
        password: password
    };
    
    console.log('Enviando datos:', data);
    
    fetch('http://localhost/URBANCOOP/APIS/API_Usuarios.php?action=login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Error parsing JSON:', e);
            throw new Error('Respuesta del servidor no válida');
        }
        
        console.log('Parsed data:', data);
        
        setButtonLoading(false);
        
        if (data.success) {
            console.log('Login exitoso:', data.user);
            
            // Almacenar datos del usuario
            sessionStorage.setItem('user_data', JSON.stringify(data.user));
            sessionStorage.setItem('login_time', new Date().getTime().toString());
            
            showMessage('¡Inicio de sesión exitoso! Redirigiendo...', 'success');
            
            // Redireccionar según el tipo de usuario
            setTimeout(() => {
                if (data.user.is_admin == 1) {
                    console.log('Redirigiendo a admin.php');
                    window.location.href = 'BACKOFFICE/admin.php';
                } else {
                    console.log('Redirigiendo a perfil.php');
                    window.location.href = 'perfil.php';
                }
            }, 1500);
            
        } else {
            showMessage(data.message || 'Error al iniciar sesión', 'error');
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        setButtonLoading(false);
        
        if (error.message.includes('fetch')) {
            showMessage('Error de conexión. Verifica que el servidor esté funcionando.', 'error');
        } else {
            showMessage('Error: ' + error.message, 'error');
        }
    });
});

// Función para validar email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Verificar si ya hay una sesión activa al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const userData = sessionStorage.getItem('user_data');
    
    if (userData) {
        try {
            const user = JSON.parse(userData);
            console.log('Usuario ya logueado:', user);
            
            showMessage('Ya tienes una sesión activa. Redirigiendo...', 'success');
            
            setTimeout(() => {
                if (user.is_admin == 1) {
                    window.location.href = 'BACKOFFICE/admin.php';
                } else {
                    window.location.href = 'perfil.php';
                }
            }, 1000);
        } catch (e) {
            console.error('Error al leer datos de sesión:', e);
            sessionStorage.clear();
        }
    }
});

// Debug: Mostrar información de conexión
console.log('=== LOGIN DEBUG INFO ===');
console.log('URL API:', 'http://localhost/URBANCOOP/API_Usuarios.php/login');
console.log('Fecha/Hora:', new Date().toLocaleString());
console.log('User Agent:', navigator.userAgent);
console.log('========================');