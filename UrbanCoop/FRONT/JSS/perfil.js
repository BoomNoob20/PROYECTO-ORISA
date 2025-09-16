// ===== URBAN COOP - PERFIL JAVASCRIPT =====
// Sistema de gestión cooperativa - Versión sin JWT

'use strict';

// === VARIABLES GLOBALES ===
const PerfilApp = {
    // Datos del usuario actual
    currentUser: {
        id: 1,
        name: 'Usuario de Prueba',
        is_admin: false
    },
    
    // Estado de la aplicación
    state: {
        isUserDataLoaded: false,
        activeSection: 'tasks',
        formsVisible: {
            upload: false,
            balance: false,
            hours: false
        }
    },
    
    // Elementos del DOM
    elements: {},
    
    // Datos simulados
    mockData: {
        tasks: [
            { id: 1, text: 'Completar registro de horas', category: 'trabajo', completed: false, favorite: false }
        ],
        payments: [
            { id: 1, month: '09', year: '2024', amount: 15000, status: 'pending', date: '2024-09-15' }
        ],
        hours: [
            { id: 1, date: '2024-09-14', hours: 8, type: 'desarrollo', description: 'Desarrollo de nuevas funcionalidades para el sistema de gestión cooperativa.' }
        ]
    }
};

// === INICIALIZACIÓN ===
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== URBAN COOP PERFIL INIT ===');
    
    // Cachear elementos del DOM
    cacheElements();
    
    // Configurar event listeners
    setupEventListeners();
    
    // Inicializar la aplicación
    initializeApp();
});

// === FUNCIONES DE INICIALIZACIÓN ===

function cacheElements() {
    PerfilApp.elements = {
        // Pantalla de carga
        loadingScreen: document.getElementById('loadingScreen'),
        mainApp: document.getElementById('mainApp'),
        
        // Navegación
        navButtons: document.querySelectorAll('.nav-button'),
        profileButton: document.querySelector('.profile-button'),
        profileDropdown: document.querySelector('.profile-dropdown'),
        profileMenu: document.getElementById('profileDropdown'),
        
        // Secciones de contenido
        sections: document.querySelectorAll('.content-section'),
        
        // Elementos de tareas
        taskList: document.getElementById('taskList'),
        addTaskBtn: document.getElementById('addTaskBtn'),
        
        // Elementos de pagos
        uploadPaymentBtn: document.getElementById('uploadPaymentBtn'),
        uploadForm: document.getElementById('upload-form'),
        uploadPaymentForm: document.getElementById('uploadPaymentForm'),
        closeUploadForm: document.getElementById('closeUploadForm'),
        cancelUpload: document.getElementById('cancelUpload'),
        uploadArea: document.getElementById('uploadArea'),
        paymentFile: document.getElementById('payment_file'),
        paymentsList: document.getElementById('paymentsList'),
        paymentMessages: document.getElementById('paymentMessages'),
        
        // Elementos de horas
        addHoursBtn: document.getElementById('addHoursBtn'),
        hoursForm: document.getElementById('hours-form'),
        hoursFormElement: document.getElementById('hoursForm'),
        closeHoursForm: document.getElementById('closeHoursForm'),
        cancelHours: document.getElementById('cancelHours'),
        hoursList: document.getElementById('hoursList'),
        hoursMessages: document.getElementById('hoursMessages'),
        
        // Elementos de resumen
        currentBalance: document.getElementById('currentBalance'),
        monthlyFee: document.getElementById('monthlyFee'),
        progressFill: document.getElementById('progressFill'),
        progressText: document.getElementById('progressText'),
        paymentStatus: document.getElementById('paymentStatus'),
        totalHoursMonth: document.getElementById('totalHoursMonth'),
        currentMonthDisplay: document.getElementById('currentMonthDisplay'),
        
        // Contadores de navegación
        navItemBadges: document.querySelectorAll('.nav-item-badge')
    };
}

function setupEventListeners() {
    // Navegación principal
    PerfilApp.elements.navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const section = btn.getAttribute('data-section');
            showSection(section);
        });
    });
    
    // Menú de perfil
    if (PerfilApp.elements.profileButton) {
        PerfilApp.elements.profileButton.addEventListener('click', toggleProfileMenu);
    }
    
    // Cerrar menú de perfil al hacer clic fuera
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.profile-dropdown')) {
            closeProfileMenu();
        }
    });
    
    // Botones de acción
    if (PerfilApp.elements.addTaskBtn) {
        PerfilApp.elements.addTaskBtn.addEventListener('click', showAddTaskDialog);
    }
    
    if (PerfilApp.elements.uploadPaymentBtn) {
        PerfilApp.elements.uploadPaymentBtn.addEventListener('click', showUploadForm);
    }
    
    if (PerfilApp.elements.addHoursBtn) {
        PerfilApp.elements.addHoursBtn.addEventListener('click', showHoursForm);
    }
    
    // Formularios
    setupFormEventListeners();
    
    // Drag & Drop para archivos
    setupFileUpload();
    
    // Teclas de acceso rápido
    document.addEventListener('keydown', handleKeyboardShortcuts);
}

function setupFormEventListeners() {
    // Formulario de pagos
    if (PerfilApp.elements.uploadPaymentForm) {
        PerfilApp.elements.uploadPaymentForm.addEventListener('submit', submitPaymentForm);
    }
    
    if (PerfilApp.elements.closeUploadForm) {
        PerfilApp.elements.closeUploadForm.addEventListener('click', hideUploadForm);
    }
    
    if (PerfilApp.elements.cancelUpload) {
        PerfilApp.elements.cancelUpload.addEventListener('click', hideUploadForm);
    }
    
    // Formulario de horas
    if (PerfilApp.elements.hoursFormElement) {
        PerfilApp.elements.hoursFormElement.addEventListener('submit', submitHoursForm);
    }
    
    if (PerfilApp.elements.closeHoursForm) {
        PerfilApp.elements.closeHoursForm.addEventListener('click', hideHoursForm);
    }
    
    if (PerfilApp.elements.cancelHours) {
        PerfilApp.elements.cancelHours.addEventListener('click', hideHoursForm);
    }
}

function setupFileUpload() {
    if (!PerfilApp.elements.uploadArea) return;
    
    const uploadArea = PerfilApp.elements.uploadArea;
    const fileInput = PerfilApp.elements.paymentFile;
    
    // Click para abrir selector de archivos
    uploadArea.addEventListener('click', (e) => {
        if (e.target === fileInput) return;
        fileInput.click();
    });
    
    // Cambio de archivo
    fileInput.addEventListener('change', (e) => {
        handleFileSelect(e.target.files[0]);
    });
    
    // Drag & Drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect(files[0]);
        }
    });
}

function initializeApp() {
    // Ocultar pantalla de carga
    hideLoadingScreen();
    
    // Mostrar aplicación principal
    PerfilApp.elements.mainApp.style.display = 'block';
    
    // Inicializar datos
    initializeData();
    
    // Configurar fecha actual
    setCurrentDate();
    
    // Marcar como cargado
    PerfilApp.state.isUserDataLoaded = true;
    
    // Mostrar mensaje de bienvenida
    showMessage('paymentMessages', 'Sistema en modo de prueba - Los datos mostrados son de ejemplo', 'success');
    
    console.log('Aplicación inicializada correctamente');
}

// === FUNCIONES DE NAVEGACIÓN ===

function showSection(sectionName) {
    // Ocultar todas las secciones
    PerfilApp.elements.sections.forEach(section => {
        section.classList.remove('active');
    });
    
    // Desactivar todos los botones de navegación
    PerfilApp.elements.navButtons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostrar la sección seleccionada
    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.add('active');
        targetSection.classList.add('animate-fadeIn');
    }
    
    // Activar el botón correspondiente
    const activeButton = document.querySelector(`[data-section="${sectionName}"]`);
    if (activeButton) {
        activeButton.classList.add('active');
    }
    
    // Actualizar estado
    PerfilApp.state.activeSection = sectionName;
    
    // Ocultar formularios si están abiertos
    hideAllForms();
}

function toggleProfileMenu() {
    const menu = PerfilApp.elements.profileMenu;
    const dropdown = PerfilApp.elements.profileDropdown;
    
    if (menu.classList.contains('show')) {
        closeProfileMenu();
    } else {
        menu.classList.add('show');
        dropdown.classList.add('active');
    }
}

function closeProfileMenu() {
    const menu = PerfilApp.elements.profileMenu;
    const dropdown = PerfilApp.elements.profileDropdown;
    
    menu.classList.remove('show');
    dropdown.classList.remove('active');
}

// === FUNCIONES DE TAREAS ===

function showAddTaskDialog() {
    const taskText = prompt('Ingresa el texto de la nueva tarea:');
    if (!taskText || !taskText.trim()) return;
    
    const category = prompt('Selecciona la categoría (trabajo/casa):') || 'trabajo';
    
    addNewTask(taskText.trim(), category);
}

function addNewTask(text, category = 'trabajo') {
    const newTask = {
        id: Date.now(),
        text: text,
        category: category,
        completed: false,
        favorite: false
    };
    
    PerfilApp.mockData.tasks.push(newTask);
    renderTask(newTask);
    updateTaskCounters();
}

function renderTask(task) {
    const taskContainer = PerfilApp.elements.taskList;
    const taskElement = document.createElement('div');
    taskElement.className = 'task-item';
    taskElement.setAttribute('data-category', task.category);
    taskElement.setAttribute('data-id', task.id);
    
    taskElement.innerHTML = `
        <div class="task-checkbox-container">
            <input type="checkbox" class="task-checkbox" id="task-${task.id}" ${task.completed ? 'checked' : ''}>
            <label for="task-${task.id}" class="checkbox-label"></label>
        </div>
        <span class="task-text">${task.text}</span>
        <div class="task-actions">
            <button class="task-action-btn star-btn ${task.favorite ? 'favorite' : ''}" title="Marcar como importante">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="${task.favorite ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2">
                    <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                </svg>
            </button>
            <button class="task-action-btn delete-btn" title="Eliminar tarea">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3,6 5,6 21,6"></polyline>
                    <path d="m19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"></path>
                </svg>
            </button>
        </div>
    `;
    
    if (task.completed) {
        taskElement.classList.add('completed');
    }
    
    // Event listeners para la nueva tarea
    const checkbox = taskElement.querySelector('.task-checkbox');
    const starBtn = taskElement.querySelector('.star-btn');
    const deleteBtn = taskElement.querySelector('.delete-btn');
    
    checkbox.addEventListener('change', () => toggleTask(task.id));
    starBtn.addEventListener('click', () => toggleFavorite(task.id));
    deleteBtn.addEventListener('click', () => deleteTask(task.id));
    
    taskContainer.appendChild(taskElement);
    
    // Animar entrada
    taskElement.classList.add('animate-slideIn');
}

function toggleTask(taskId) {
    const task = PerfilApp.mockData.tasks.find(t => t.id == taskId);
    if (!task) return;
    
    task.completed = !task.completed;
    
    const taskElement = document.querySelector(`[data-id="${taskId}"]`);
    if (taskElement) {
        taskElement.classList.toggle('completed', task.completed);
    }
    
    updateTaskCounters();
}

function toggleFavorite(taskId) {
    const task = PerfilApp.mockData.tasks.find(t => t.id == taskId);
    if (!task) return;
    
    task.favorite = !task.favorite;
    
    const starBtn = document.querySelector(`[data-id="${taskId}"] .star-btn`);
    const svg = starBtn.querySelector('svg');
    
    if (task.favorite) {
        starBtn.classList.add('favorite');
        svg.setAttribute('fill', 'currentColor');
    } else {
        starBtn.classList.remove('favorite');
        svg.setAttribute('fill', 'none');
    }
    
    updateTaskCounters();
}

function deleteTask(taskId) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta tarea?')) return;
    
    // Remover de los datos
    PerfilApp.mockData.tasks = PerfilApp.mockData.tasks.filter(t => t.id != taskId);
    
    // Remover del DOM
    const taskElement = document.querySelector(`[data-id="${taskId}"]`);
    if (taskElement) {
        taskElement.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            taskElement.remove();
        }, 300);
    }
    
    updateTaskCounters();
}

function updateTaskCounters() {
    const tasks = PerfilApp.mockData.tasks;
    const totalTasks = tasks.length;
    const favoriteTasks = tasks.filter(t => t.favorite).length;
    const casaTasks = tasks.filter(t => t.category === 'casa').length;
    
    const badges = PerfilApp.elements.navItemBadges;
    if (badges[0]) badges[0].textContent = favoriteTasks;
    if (badges[1]) badges[1].textContent = totalTasks;
    if (badges[2]) badges[2].textContent = casaTasks;
}

// === FUNCIONES DE PAGOS ===

function showUploadForm() {
    PerfilApp.elements.uploadForm.style.display = 'block';
    PerfilApp.elements.uploadForm.classList.add('animate-fadeIn');
    PerfilApp.state.formsVisible.upload = true;
    
    // Scroll al formulario
    PerfilApp.elements.uploadForm.scrollIntoView({ behavior: 'smooth' });
}

function hideUploadForm() {
    PerfilApp.elements.uploadForm.style.display = 'none';
    PerfilApp.state.formsVisible.upload = false;
    
    // Limpiar formulario
    if (PerfilApp.elements.uploadPaymentForm) {
        PerfilApp.elements.uploadPaymentForm.reset();
    }
    
    // Resetear área de upload
    resetUploadArea();
}

function handleFileSelect(file) {
    if (!file) return;
    
    // Validar tipo de archivo
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    if (!allowedTypes.includes(file.type)) {
        showMessage('paymentMessages', 'Tipo de archivo no válido. Use PDF, JPG o PNG.', 'error');
        return;
    }
    
    // Validar tamaño (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showMessage('paymentMessages', 'El archivo es demasiado grande. Máximo 5MB.', 'error');
        return;
    }
    
    // Mostrar información del archivo
    const uploadText = PerfilApp.elements.uploadArea.querySelector('.upload-text');
    if (uploadText) {
        uploadText.textContent = `Archivo seleccionado: ${file.name}`;
    }
}

function resetUploadArea() {
    const uploadText = PerfilApp.elements.uploadArea.querySelector('.upload-text');
    if (uploadText) {
        uploadText.textContent = 'Arrastra y suelta tu archivo aquí';
    }
    
    if (PerfilApp.elements.paymentFile) {
        PerfilApp.elements.paymentFile.value = '';
    }
}

function submitPaymentForm(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const month = formData.get('payment_month');
    const year = formData.get('payment_year');
    const amount = formData.get('payment_amount');
    const file = formData.get('payment_file');
    
    // Validación
    if (!file || !month || !year || !amount) {
        showMessage('paymentMessages', 'Por favor completa todos los campos requeridos', 'error');
        return;
    }
    
    if (amount < 1000 || amount > 1000000) {
        showMessage('paymentMessages', 'El monto debe estar entre $1.000 y $1.000.000', 'error');
        return;
    }
    
    // Simular envío
    setTimeout(() => {
        // Agregar a datos simulados
        const newPayment = {
            id: Date.now(),
            month: month,
            year: year,
            amount: parseFloat(amount),
            status: 'pending',
            date: new Date().toISOString().split('T')[0],
            description: formData.get('payment_description') || ''
        };
        
        PerfilApp.mockData.payments.push(newPayment);
        
        showMessage('paymentMessages', 'Comprobante subido exitosamente (simulado). Pendiente de aprobación.', 'success');
        hideUploadForm();
        
        // Actualizar vista de pagos
        renderPaymentCard(newPayment);
        
    }, 1000);
}

function renderPaymentCard(payment) {
    const monthNames = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    
    const paymentCard = document.createElement('div');
    paymentCard.className = 'payment-card animate-slideIn';
    paymentCard.innerHTML = `
        <div class="payment-info">
            <h3 class="payment-title">Comprobante ${monthNames[parseInt(payment.month)]} ${payment.year}</h3>
            <div class="payment-details">
                <span class="payment-amount">$${payment.amount.toLocaleString()}</span>
                <span class="payment-date">${formatDate(payment.date)}</span>
                <span class="payment-size">Documento</span>
            </div>
        </div>
        <div class="payment-actions">
            <span class="payment-status ${payment.status}">${getStatusText(payment.status)}</span>
            <button class="action-button secondary small" onclick="viewPayment(${payment.id})">Ver</button>
        </div>
    `;
    
    PerfilApp.elements.paymentsList.insertBefore(paymentCard, PerfilApp.elements.paymentsList.firstChild);
}

function getStatusText(status) {
    const statusMap = {
        'pending': 'Pendiente',
        'approved': 'Aprobado',
        'rejected': 'Rechazado'
    };
    return statusMap[status] || status;
}

function viewPayment(paymentId) {
    alert('Función en desarrollo - Ver comprobante #' + paymentId);
}

// === FUNCIONES DE HORAS ===

function showHoursForm() {
    PerfilApp.elements.hoursForm.style.display = 'block';
    PerfilApp.elements.hoursForm.classList.add('animate-fadeIn');
    PerfilApp.state.formsVisible.hours = true;
    
    // Establecer fecha actual por defecto
    const today = new Date().toISOString().split('T')[0];
    const dateInput = PerfilApp.elements.hoursForm.querySelector('input[name="work_date"]');
    if (dateInput && !dateInput.value) {
        dateInput.value = today;
    }
    
    // Scroll al formulario
    PerfilApp.elements.hoursForm.scrollIntoView({ behavior: 'smooth' });
}

function hideHoursForm() {
    PerfilApp.elements.hoursForm.style.display = 'none';
    PerfilApp.state.formsVisible.hours = false;
    
    // Limpiar formulario
    if (PerfilApp.elements.hoursFormElement) {
        PerfilApp.elements.hoursFormElement.reset();
    }
}

function submitHoursForm(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const workDate = formData.get('work_date');
    const hours = formData.get('hours_worked');
    const description = formData.get('description');
    const workType = formData.get('work_type');
    
    // Validación
    if (!workDate || !hours || !description || !workType) {
        showMessage('hoursMessages', 'Por favor completa todos los campos requeridos', 'error');
        return;
    }
    
    if (hours < 0.5 || hours > 24) {
        showMessage('hoursMessages', 'Las horas deben estar entre 0.5 y 24', 'error');
        return;
    }
    
    // Validar que la fecha no sea futura
    const selectedDate = new Date(workDate);
    const today = new Date();
    today.setHours(23, 59, 59, 999);
    
    if (selectedDate > today) {
        showMessage('hoursMessages', 'No puedes registrar horas de fechas futuras', 'error');
        return;
    }
    
    // Simular registro
    setTimeout(() => {
        const newHours = {
            id: Date.now(),
            date: workDate,
            hours: parseFloat(hours),
            type: workType,
            description: description.trim(),
            created_at: new Date().toISOString()
        };
        
        PerfilApp.mockData.hours.push(newHours);
        
        showMessage('hoursMessages', 'Horas registradas exitosamente (simulado)', 'success');
        hideHoursForm();
        
        // Actualizar resumen de horas
        updateHoursSummary();
        
        // Renderizar nueva entrada
        renderHoursCard(newHours);
        
    }, 1000);
}

function updateHoursSummary() {
    const currentMonth = new Date().getMonth();
    const currentYear = new Date().getFullYear();
    
    const monthlyHours = PerfilApp.mockData.hours
        .filter(h => {
            const hDate = new Date(h.date);
            return hDate.getMonth() === currentMonth && hDate.getFullYear() === currentYear;
        })
        .reduce((total, h) => total + h.hours, 0);
    
    if (PerfilApp.elements.totalHoursMonth) {
        PerfilApp.elements.totalHoursMonth.textContent = `${monthlyHours} horas`;
    }
}

function renderHoursCard(hoursData) {
    const typeLabels = {
        'desarrollo': 'Desarrollo',
        'reunion': 'Reuniones',
        'documentacion': 'Documentación',
        'testing': 'Testing',
        'administrativo': 'Administrativo',
        'soporte': 'Soporte Técnico',
        'investigacion': 'Investigación',
        'otros': 'Otros'
    };
    
    const hoursCard = document.createElement('div');
    hoursCard.className = 'hours-card animate-slideIn';
    hoursCard.innerHTML = `
        <div class="hours-info">
            <h3 class="hours-title">${formatDate(hoursData.date)} - ${hoursData.hours} horas</h3>
            <div class="hours-type">${typeLabels[hoursData.type] || hoursData.type}</div>
            <p class="hours-description">${hoursData.description}</p>
            <small class="hours-date">Registrado el ${formatDateTime(hoursData.created_at)}</small>
        </div>
        <div class="hours-actions">
            <button class="action-button danger small" onclick="deleteHours(${hoursData.id})">Eliminar</button>
        </div>
    `;
    
    PerfilApp.elements.hoursList.insertBefore(hoursCard, PerfilApp.elements.hoursList.firstChild);
}

function deleteHours(hoursId) {
    if (!confirm('¿Estás seguro de que quieres eliminar este registro de horas?')) return;
    
    // Remover de datos
    PerfilApp.mockData.hours = PerfilApp.mockData.hours.filter(h => h.id != hoursId);
    
    // Remover del DOM
    const hoursCard = document.querySelector([onclick="deleteHours(${hoursId})"]).closest('.hours-card');
    if (hoursCard) {
        hoursCard.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            hoursCard.remove();
            updateHoursSummary();
        }, 300);
    }
}

// === FUNCIONES DE UTILIDAD ===

function hideLoadingScreen() {
    if (PerfilApp.elements.loadingScreen) {
        PerfilApp.elements.loadingScreen.style.display = 'none';
    }
}

function showMessage(containerId, message, type = 'info') {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const alertClass = `alert-${type}`;
    
    // Crear elemento de mensaje
    const messageElement = document.createElement('div');
    messageElement.className = `alert ${alertClass} animate-slideIn`;
    messageElement.textContent = message;
    
    // Limpiar mensajes anteriores
    container.innerHTML = '';
    container.appendChild(messageElement);
    
    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
        if (messageElement.parentNode) {
            messageElement.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                messageElement.remove();
            }, 300);
        }
    }, 5000);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function setCurrentDate() {
    const now = new Date();
    const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    
    const currentMonthText = `${monthNames[now.getMonth()]} ${now.getFullYear()}`;
    
    if (PerfilApp.elements.currentMonthDisplay) {
        PerfilApp.elements.currentMonthDisplay.textContent = currentMonthText;
    }
}

function initializeData() {
    // Inicializar progreso de pago
    const currentBalance = 15000;
    const monthlyFee = 22000;
    const progress = Math.min((currentBalance / monthlyFee) * 100, 100);
    
    if (PerfilApp.elements.progressFill) {
        PerfilApp.elements.progressFill.style.width = progress + '%';
        PerfilApp.elements.progressFill.setAttribute('data-progress', progress);
    }
    
    if (PerfilApp.elements.progressText) {
        PerfilApp.elements.progressText.textContent = `${Math.round(progress)}%`;
    }
    
    if (PerfilApp.elements.paymentStatus) {
        PerfilApp.elements.paymentStatus.textContent = progress >= 100 ? 'Completo' : 'Al día';
    }
    
    // Actualizar contadores
    updateTaskCounters();
    updateHoursSummary();
    
    // Configurar nombre de usuario
    const userNameDisplay = document.getElementById('userNameDisplay');
    if (userNameDisplay) {
        userNameDisplay.textContent = PerfilApp.currentUser.name;
    }
}

function hideAllForms() {
    hideUploadForm();
    hideHoursForm();
}

function handleKeyboardShortcuts(event) {
    // Solo procesar si no estamos en un input
    if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
        return;
    }
    
    // Ctrl/Cmd + tecla
    if (event.ctrlKey || event.metaKey) {
        switch (event.key) {
            case '1':
                event.preventDefault();
                showSection('tasks');
                break;
            case '2':
                event.preventDefault();
                showSection('payments');
                break;
            case '3':
                event.preventDefault();
                showSection('hours');
                break;
        }
    }
    
    // Escape para cerrar formularios
    if (event.key === 'Escape') {
        hideAllForms();
        closeProfileMenu();
    }
}

// === FUNCIONES GLOBALES (para uso en HTML) ===

function logout() {
    if (confirm('¿Estás seguro que quieres cerrar sesión?')) {
        // Limpiar datos locales
        PerfilApp.mockData = { tasks: [], payments: [], hours: [] };
        
        // Redirigir
        window.location.href = 'index.php';
    }
}

// Hacer funciones disponibles globalmente para eventos onclick en HTML
window.logout = logout;
window.viewPayment = viewPayment;
window.deleteHours = deleteHours;

// === ANIMACIONES CSS ADICIONALES ===

// Agregar estilos de animación dinámicamente
const additionalStyles = document.createElement('style');
additionalStyles.textContent = `
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }
    
    .animate-fadeOut {
        animation: fadeOut 0.3s ease-out forwards;
    }
    
    /* Mejoras visuales para estados interactivos */
    .task-item:hover {
        transform: translateX(4px);
    }
    
    .payment-card:hover,
    .hours-card:hover {
        border-left: 4px solid var(--color-primary);
    }
    
    .summary-card:hover .card-icon {
        transform: scale(1.1);
    }
    
    .action-button:active {
        transform: translateY(1px);
    }
    
    /* Estados de carga */
    .form-container.loading {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .form-container.loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
    }
`;

document.head.appendChild(additionalStyles);

// === DEBUG Y DESARROLLO ===

if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    // Funciones de debug solo en desarrollo
    window.PerfilApp = PerfilApp;
    
    console.log('🔧 Modo desarrollo activado');
    console.log('Datos disponibles:', PerfilApp.mockData);
    console.log('Estado de la app:', PerfilApp.state);
    
    // Atajos de teclado para desarrollo
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.shiftKey) {
            switch (e.key) {
                case 'D':
                    e.preventDefault();
                    console.table(PerfilApp.mockData.tasks);
                    break;
                case 'P':
                    e.preventDefault();
                    console.table(PerfilApp.mockData.payments);
                    break;
                case 'H':
                    e.preventDefault();
                    console.table(PerfilApp.mockData.hours);
                    break;
            }
        }
    });
}

console.log('✅ Urban Coop Perfil - Sistema cargado correctamente');