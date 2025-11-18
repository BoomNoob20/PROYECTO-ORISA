class SessionManager {
    static SESSION_KEY = 'user_data';
    static LOGIN_TIME_KEY = 'login_time';
    static MAX_SESSION_TIME = 24 * 60 * 60 * 1000; // 24 horas

    // Verificar si hay una sesión válida
    static isLoggedIn() {
        const userData = sessionStorage.getItem(this.SESSION_KEY);
        const loginTime = sessionStorage.getItem(this.LOGIN_TIME_KEY);
        
        if (!userData || !loginTime) {
            return false;
        }
        
        // Verificar si la sesión no ha expirado
        const currentTime = new Date().getTime();
        const sessionAge = currentTime - parseInt(loginTime);
        
        if (sessionAge > this.MAX_SESSION_TIME) {
            this.logout();
            return false;
        }
        
        return true;
    }

    // Obtener datos del usuario actual
    static getCurrentUser() {
        if (!this.isLoggedIn()) {
            return null;
        }
        
        try {
            const userData = sessionStorage.getItem(this.SESSION_KEY);
            return JSON.parse(userData);
        } catch (e) {
            console.error('Error al parsear datos de usuario:', e);
            this.logout();
            return null;
        }
    }

    // Verificar si el usuario es admin
    static isAdmin() {
        const user = this.getCurrentUser();
        return user && user.is_admin == 1;
    }

    // Cerrar sesión
    static logout() {
        sessionStorage.removeItem(this.SESSION_KEY);
        sessionStorage.removeItem(this.LOGIN_TIME_KEY);
        
        // Opcional: notificar al servidor sobre el logout
        // fetch('/api/logout', { method: 'POST' });
        
        window.location.href = 'login.html';
    }

    // Proteger página - llamar al inicio de páginas que requieren autenticación
    static requireAuth() {
        if (!this.isLoggedIn()) {
            alert('Debes iniciar sesión para acceder a esta página');
            window.location.href = 'login.html';
            return false;
        }
        return true;
    }

    // Proteger página de admin
    static requireAdmin() {
        if (!this.requireAuth()) {
            return false;
        }
        
        if (!this.isAdmin()) {
            alert('No tienes permisos de administrador para acceder a esta página');
            window.location.href = 'perfil.php';
            return false;
        }
        return true;
    }

    // Guardar sesión (llamar después del login exitoso)
    static saveSession(userData) {
        sessionStorage.setItem(this.SESSION_KEY, JSON.stringify(userData));
        sessionStorage.setItem(this.LOGIN_TIME_KEY, new Date().getTime().toString());
    }

    // Actualizar tiempo de última actividad
    static updateActivity() {
        if (this.isLoggedIn()) {
            sessionStorage.setItem(this.LOGIN_TIME_KEY, new Date().getTime().toString());
        }
    }

    // Crear botón de logout dinámicamente
    static createLogoutButton(containerId) {
        const container = document.getElementById(containerId);
        if (container && this.isLoggedIn()) {
            const user = this.getCurrentUser();
            container.innerHTML = `
                <div class="user-info">
                    <span>Hola, ${user.name}!</span>
                    <button onclick="SessionManager.logout()" class="logout-btn">Cerrar Sesión</button>
                </div>
            `;
        }
    }

    // Inicializar el manager (llamar en cada página)
    static init() {
        // Actualizar actividad en cada interacción
        document.addEventListener('click', () => this.updateActivity());
        document.addEventListener('keypress', () => this.updateActivity());
        
        // Verificar sesión periódicamente
        setInterval(() => {
            if (!this.isLoggedIn()) {
                console.log('Sesión expirada');
                this.logout();
            }
        }, 60000); // Verificar cada minuto
    }
}

// Inicializar automáticamente si el script se carga
document.addEventListener('DOMContentLoaded', function() {
    SessionManager.init();
});