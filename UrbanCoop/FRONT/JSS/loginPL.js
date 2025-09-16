const themeToggle = document.getElementById('themeToggle');
const body = document.body;

let currentTheme = 'light';

function toggleTheme() {
    currentTheme = currentTheme === 'light' ? 'dark' : 'light';
    body.setAttribute('data-theme', currentTheme);
}

themeToggle.addEventListener('click', toggleTheme);

// FUNCIÓN MEJORADA: validación de sesión sin JWT
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
        
        // Verificar tiempo de expiración (24 horas)
        const SESSION_TIMEOUT = 24 * 60 * 60 * 1000;
        if (now - login > SESSION_TIMEOUT) {
            clearSession();
            return false;
        }
        
        return true;
    } catch (e) {
        console.error('Error validating session:', e);
        clearSession();
        return false;
    }
}

// FUNCIÓN para limpiar sesión
function clearSession() {
    localStorage.removeItem('user_data');
    localStorage.removeItem('login_time');
    sessionStorage.removeItem('user_data');
    sessionStorage.removeItem('login_time');
    console.log('Sesión limpiada');
}

// FUNCIÓN para obtener datos del usuario actual
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

// Funciones de mensajes
function showMessage(message, type) {
    const messageArea = document.getElementById('message-area');
    if (!messageArea) {
        console.warn('Message area element not found');
        return;
    }
    
    const messageClass = type === 'error' ? 'error-message' : 'success-message';
    messageArea.innerHTML = `<div class="${messageClass}">${message}</div>`;
    
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

// FUNCIÓN MEJORADA para establecer sesión PHP (sin JWT)
async function establishPhpSession(userData) {
    const establishSessionUrl = getApiBaseUrl().replace('/API_Usuarios.php', '/establish_session.php');
    
    console.log('=== ESTABLISHING PHP SESSION ===');
    console.log('URL:', establishSessionUrl);
    
    try {
        const response = await fetch(establishSessionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            },
            body: JSON.stringify({
                action: 'establish_session',
                user_id: userData.id,
                user_email: userData.email,
                user_name: userData.name,
                user_surname: userData.surname,
                is_admin: userData.is_admin,
                estado: userData.estado
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('Establish session result:', result);
        
        if (!result.success) {
            throw new Error(result.message || 'Failed to establish PHP session');
        }
        
        console.log('✅ PHP session established successfully');
        return result;
        
    } catch (error) {
        console.error('❌ Error establishing PHP session:', error);
        // No lanzar error aquí - permitir que el login continúe
        console.warn('Continuando sin sesión PHP establecida');
        return { success: false, message: error.message };
    }
}

// Función para determinar la URL base correcta
function getApiBaseUrl() {
    const currentLocation = window.location;
    const protocol = currentLocation.protocol;
    const hostname = currentLocation.hostname;
    const port = currentLocation.port;
    
    let baseUrl = `${protocol}//${hostname}`;
    if (port && port !== '80' && port !== '443') {
        baseUrl += `:${port}`;
    }
    
    return `${baseUrl}/URBANCOOP/APIS/API_Usuarios.php`;
}

// FUNCIÓN SIMPLIFICADA para almacenar sesión (sin JWT)
function storeUserSession(userData) {
    const sessionData = {
        id: userData.id,
        name: userData.name || userData.usr_name,
        surname: userData.surname || userData.usr_surname,
        email: userData.email || userData.usr_email,
        phone: userData.phone || userData.usr_phone,
        ci: userData.ci || userData.usr_ci,
        is_admin: parseInt(userData.is_admin) || 0,
        estado: userData.estado
    };
    
    const loginTime = new Date().getTime().toString();
    
    // Almacenar en ambos storages para compatibilidad
    localStorage.setItem('user_data', JSON.stringify(sessionData));
    localStorage.setItem('login_time', loginTime);
    sessionStorage.setItem('user_data', JSON.stringify(sessionData));
    sessionStorage.setItem('login_time', loginTime);
    
    console.log('=== SESSION STORED ===');
    console.log('User data:', sessionData);
    console.log('Is Admin:', sessionData.is_admin, typeof sessionData.is_admin);
    console.log('=====================');
    
    return sessionData;
}

// FUNCIÓN MEJORADA para redirección
function redirectUser(user) {
    console.log('=== REDIRECTION LOGIC ===');
    console.log('User is_admin value:', user.is_admin);
    console.log('Type of is_admin:', typeof user.is_admin);
    
    // Verificación robusta del rol de admin
    const isAdmin = user.is_admin === 1 || user.is_admin === '1' || user.is_admin == 1;
    
    console.log('Is admin check result:', isAdmin);
    
    if (isAdmin) {
        console.log('✅ Redirecting to admin panel');
        window.location.href = 'BACKOFFICE/admin.php';
    } else {
        console.log('✅ Redirecting to user profile');
        window.location.href = 'perfil.php';
    }
    console.log('========================');
}

// MANEJO DEL FORMULARIO DE LOGIN (MEJORADO)
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
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
    
    setButtonLoading(true);
    
    const data = {
        email: email,
        password: password
    };
    
    const apiUrl = `${getApiBaseUrl()}?action=login`;
    
    console.log('=== API REQUEST DEBUG ===');
    console.log('API URL:', apiUrl);
    console.log('Enviando datos:', data);
    console.log('========================');
    
    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            },
            body: JSON.stringify(data)
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        let responseData;
        try {
            responseData = JSON.parse(text);
        } catch (e) {
            console.error('JSON Parse Error:', e);
            throw new Error('Respuesta del servidor no válida');
        }
        
        console.log('Parsed data:', responseData);
        setButtonLoading(false);
        
        if (responseData.success) {
            console.log('Login exitoso:', responseData.user);
            
            // Limpiar sesión anterior
            clearSession();
            
            // Almacenar datos del usuario
            const storedUser = storeUserSession(responseData.user);
            
            // Mostrar mensaje de éxito
            showMessage('¡Inicio de sesión exitoso! Redirigiendo...', 'success');
            
            // ESTABLECER SESIÓN PHP DE FORMA ASÍNCRONA (no bloqueante)
            establishPhpSession(storedUser).then(result => {
                if (result.success) {
                    console.log('✅ PHP session established');
                } else {
                    console.warn('⚠️ PHP session not established, but continuing...');
                }
            });
            
            // REDIRECCIONAR INMEDIATAMENTE (sin esperar la sesión PHP)
            setTimeout(() => {
                redirectUser(storedUser);
            }, 1500);
            
        } else {
            showMessage(responseData.message || 'Error al iniciar sesión', 'error');
        }
        
    } catch (error) {
        console.error('Login error:', error);
        setButtonLoading(false);
        
        if (error.message.includes('404')) {
            showMessage('Error 404: API no encontrada', 'error');
        } else if (error.message.includes('Failed to fetch')) {
            showMessage('Error de conexión con el servidor', 'error');
        } else {
            showMessage('Error: ' + error.message, 'error');
        }
    }
});

// Función para validar email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// VERIFICACIÓN DE SESIÓN AL CARGAR LA PÁGINA (MEJORADA)
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== SESSION CHECK DEBUG ===');
    console.log('Current URL:', window.location.href);
    console.log('localStorage user_data:', localStorage.getItem('user_data'));
    console.log('isSessionValid():', isSessionValid());
    
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
                // Ya está logueado - redirigir
                showMessage('Ya tienes una sesión activa. Redirigiendo...', 'success');
                setTimeout(() => {
                    redirectUser(user);
                }, 1000);
            } else if (isAdminPage && user.is_admin != 1) {
                // Usuario normal tratando de acceder a área admin
                showMessage('No tienes permisos para acceder a esta página', 'error');
                setTimeout(() => {
                    window.location.href = 'perfil.php';
                }, 1500);
            }
        }
    } else {
        // No hay sesión válida
        if (!isLoginPage) {
            showMessage('Por favor inicia sesión para acceder', 'error');
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1500);
        }
    }
    
    console.log('=========================');
});

// FUNCIÓN PARA LOGOUT
function logout() {
    clearSession();
    showMessage('Sesión cerrada correctamente', 'success');
    setTimeout(() => {
        window.location.href = 'index.html';
    }, 1000);
}

// DEBUG INFO
console.log('=== LOGIN DEBUG INFO ===');
console.log('API Base URL:', getApiBaseUrl());
console.log('Current Location:', window.location.href);
console.log('Timestamp:', new Date().toLocaleString());
console.log('========================');