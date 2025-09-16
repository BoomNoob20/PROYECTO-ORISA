const themeToggle = document.getElementById('themeToggle');
const body = document.body;

let currentTheme = 'light';

function toggleTheme() {
    currentTheme = currentTheme === 'light' ? 'dark' : 'light';
    body.setAttribute('data-theme', currentTheme);
}

themeToggle.addEventListener('click', toggleTheme);

// * FUNCIÓN CRÍTICA: isSessionValid() - ESTABA FALTANDO *
function isSessionValid() {
    const userData = localStorage.getItem('user_data');
    const loginTime = localStorage.getItem('login_time');
    
    if (!userData || !loginTime) {
        return false;
    }
    
    try {
        const user = JSON.parse(userData);
        const now = new Date().getTime();
        const login = parseInt(loginTime);
        
        // Verificar que los datos del usuario sean válidos
        if (!user.id || !user.email) {
            return false;
        }
        
        // Opcional: verificar que no hayan pasado más de X horas (ej: 24 horas = 86400000 ms)
        const SESSION_TIMEOUT = 24 * 60 * 60 * 1000; // 24 horas
        if (now - login > SESSION_TIMEOUT) {
            // Limpiar sesión expirada
            localStorage.removeItem('user_data');
            localStorage.removeItem('login_time');
            return false;
        }
        
        return true;
    } catch (e) {
        console.error('Error validating session:', e);
        // Limpiar datos corruptos
        localStorage.removeItem('user_data');
        localStorage.removeItem('login_time');
        return false;
    }
}

// * FUNCIÓN PARA LIMPIAR SESIÓN *
function clearSession() {
    localStorage.removeItem('user_data');
    localStorage.removeItem('login_time');
    console.log('Sesión limpiada');
}

// * FUNCIÓN PARA OBTENER DATOS DEL USUARIO *
function getCurrentUser() {
    if (!isSessionValid()) {
        return null;
    }
    
    try {
        const userData = localStorage.getItem('user_data');
        return JSON.parse(userData);
    } catch (e) {
        console.error('Error getting current user:', e);
        clearSession();
        return null;
    }
}

// Message functions
function showMessage(message, type) {
    const messageArea = document.getElementById('message-area');
    if (!messageArea) {
        console.warn('Message area element not found');
        return;
    }
    
    const messageClass = type === 'error' ? 'error-message' : 'success-message';
    
    messageArea.innerHTML = `<div class="${messageClass}">${message}</div>`;
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageArea.innerHTML = '';
    }, 5000);
}

function setButtonLoading(loading) {
    const button = document.getElementById('loginButton');
    if (!button) {
        console.warn('Login button element not found');
        return;
    }
    
    if (loading) {
        button.disabled = true;
        button.innerHTML = '<span class="loading"></span>Iniciando sesión...';
    } else {
        button.disabled = false;
        button.innerHTML = 'SIGN IN';
    }
}

// Función para determinar la URL base correcta
function getApiBaseUrl() {
    // Obtener la URL actual de la página
    const currentLocation = window.location;
    const protocol = currentLocation.protocol; // http: o https:
    const hostname = currentLocation.hostname; // localhost o tu dominio
    const port = currentLocation.port; // puerto si existe
    
    // Construir la URL base
    let baseUrl = `${protocol}//${hostname}`;
    if (port && port !== '80' && port !== '443') {
        baseUrl += `:${port}`;
    }
    
    // Agregar la ruta al API
    return `${baseUrl}/URBANCOOP/APIS/API_Usuarios.php`;
}

// Form functionality
document.getElementById('loginForm')?.addEventListener('submit', function(e) {
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
    
    // Obtener la URL del API dinámicamente
    const apiUrl = `${getApiBaseUrl()}?action=login`;
    
    console.log('=== API REQUEST DEBUG ===');
    console.log('API URL:', apiUrl);
    console.log('Enviando datos:', data);
    console.log('Current location:', window.location.href);
    console.log('========================');
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Cache-Control': 'no-cache'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('=== API RESPONSE DEBUG ===');
        console.log('Response status:', response.status);
        console.log('Response statusText:', response.statusText);
        console.log('Response URL:', response.url);
        console.log('Response headers:', [...response.headers.entries()]);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText} - URL: ${response.url}`);
        }
        
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON Parse Error:', e);
            console.error('Respuesta recibida:', text);
            throw new Error('Respuesta del servidor no válida. Posible error en el servidor PHP.');
        }
        
        console.log('Parsed data:', data);
        
        setButtonLoading(false);
        
        if (data.success) {
            console.log('Login exitoso:', data.user);
            
            // Limpiar sesión anterior antes de crear nueva
            clearSession();
            
            // Almacenar datos del usuario
            localStorage.setItem('user_data', JSON.stringify(data.user));
            localStorage.setItem('login_time', new Date().getTime().toString());
            
            // Verificar que se guardó correctamente
            console.log('=== SESSION STORAGE DEBUG ===');
            console.log('user_data:', localStorage.getItem('user_data'));
            console.log('login_time:', localStorage.getItem('login_time'));
            console.log('isSessionValid():', isSessionValid());
            console.log('============================');
            
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
        console.error('=== FETCH ERROR DEBUG ===');
        console.error('Error completo:', error);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        console.error('========================');
        
        setButtonLoading(false);
        
        if (error.message.includes('404')) {
            showMessage('Error 404: API no encontrada. Verifica la ruta del servidor.', 'error');
        } else if (error.message.includes('Failed to fetch')) {
            showMessage('Error de conexión. Verifica que el servidor esté funcionando.', 'error');
        } else if (error.message.includes('NetworkError')) {
            showMessage('Error de red. Verifica tu conexión a internet.', 'error');
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

// * VERIFICACIÓN DE SESIÓN AL CARGAR LA PÁGINA - CORREGIDA *
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== SESSION CHECK DEBUG ===');
    console.log('Current URL:', window.location.href);
    console.log('localStorage user_data:', localStorage.getItem('user_data'));
    console.log('localStorage login_time:', localStorage.getItem('login_time'));
    console.log('isSessionValid():', isSessionValid());
    console.log('API Base URL:', getApiBaseUrl());
    
    // Detectar el tipo de página actual
    const isLoginPage = window.location.pathname.includes('login') || 
                       window.location.pathname.includes('index') ||
                       window.location.pathname.endsWith('/') ||
                       document.getElementById('loginForm') !== null;
    
    const isAdminPage = window.location.pathname.includes('admin') || 
                       window.location.pathname.includes('BACKOFFICE');
    
    console.log('isLoginPage:', isLoginPage);
    console.log('isAdminPage:', isAdminPage);
    
    if (isSessionValid()) {
        const user = getCurrentUser();
        console.log('Usuario activo:', user);
        
        if (user) {
            if (isLoginPage) {
                // Ya está logueado y está en la página de login - redirigir
                showMessage('Ya tienes una sesión activa. Redirigiendo...', 'success');
                setTimeout(() => {
                    if (user.is_admin == 1) {
                        window.location.href = 'BACKOFFICE/admin.php';
                    } else {
                        window.location.href = 'perfil.php';
                    }
                }, 1000);
            } else if (isAdminPage && user.is_admin != 1) {
                // Usuario normal tratando de acceder a área admin
                showMessage('No tienes permisos para acceder a esta página', 'error');
                setTimeout(() => {
                    window.location.href = 'perfil.php';
                }, 1500);
            }
            // Si está en una página normal y tiene sesión válida, todo OK
        }
    } else {
        // No hay sesión válida
        console.log('No hay sesión válida');
        
        if (!isLoginPage) {
            // Está en una página protegida sin sesión - redirigir al login
            showMessage('Por favor inicia sesión para acceder', 'error');
            setTimeout(() => {
                window.location.href = 'index.html'; // O tu página de login
            }, 1500);
        }
    }
    
    console.log('=========================');
});

// * FUNCIÓN PARA LOGOUT (opcional) *
function logout() {
    clearSession();
    showMessage('Sesión cerrada correctamente', 'success');
    setTimeout(() => {
        window.location.href = 'index.html';
    }, 1000);
}

// Función para probar la conectividad con el API
function testApiConnection() {
    const apiUrl = getApiBaseUrl();
    console.log('Probando conexión con API:', apiUrl);
    
    fetch(apiUrl, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => {
        console.log('Test API Response:', response.status, response.statusText);
        return response.text();
    })
    .then(text => {
        console.log('Test API Response body:', text);
    })
    .catch(error => {
        console.error('Test API Error:', error);
    });
}

// Debug: Mostrar información de conexión
console.log('=== LOGIN DEBUG INFO ===');
console.log('API Base URL:', getApiBaseUrl());
console.log('Current Location:', window.location.href);
console.log('Fecha/Hora:', new Date().toLocaleString());
console.log('User Agent:', navigator.userAgent);
console.log('========================');

// Probar conexión al cargar (solo en desarrollo)
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    testApiConnection();
}