// ===== URBAN COOP - PERFIL JAVASCRIPT MEJORADO =====
// Sistema de gestión cooperativa con pago inicial y dashboard

'use strict';

// === VARIABLES GLOBALES ===
const PerfilApp = {
    // Datos del usuario actual
    currentUser: {
        id: 1,
        name: 'Usuario de Prueba',
        is_admin: false,
        hasInitialPayment: false, // Nuevo campo
        hasUnit: false,
        monthlyFee: 22000,
        totalPaid: 15000,
        approvedPaid: 10000,
        requiredHours: 20,
        totalHoursMonth: 12,
        meetingsAttended: 1,
        requiredMeetings: 3
    },
    
    // Estado de la aplicación
    state: {
        isUserDataLoaded: false,
        activeSection: 'dashboard',
        formsVisible: {
            upload: false,
            hours: false
        },
        expandedMenus: {
            payments: false,
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
            { id: 1, date: '2024-09-14', hours: 8, type: 'desarrollo', description: 'Desarrollo de nuevas funcionalidades.' }
        ],
        upcomingMeetings: [
            { id: 1, date: '2025-10-15', title: 'Reunión Mensual', required: true, attended: false },
            { id: 2, date: '2025-10-22', title: 'Asamblea General', required: true, attended: false },
            { id: 3, date: '2025-10-28', title: 'Reunión de Comité', required: true, attended: false }
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
        
        // Modal de pago inicial
        initialPaymentModal: document.getElementById('initialPaymentModal'),
        
        // Navegación
        navButtons: document.querySelectorAll('.nav-button'),
        navToggles: document.querySelectorAll('.nav-toggle'),
        navSubmenus: document.querySelectorAll('.nav-submenu'),
        profileButton: document.querySelector('.profile-button'),
        profileDropdown: document.querySelector('.profile-dropdown'),
        profileMenu: document.getElementById('profileDropdown'),
        
        // Secciones de contenido
        sections: document.querySelectorAll('.content-section'),
        
        // Dashboard
        noAccessWarning: document.getElementById('noAccessWarning'),
        monthlyFee: document.getElementById('monthlyFee'),
        totalPaid: document.getElementById('totalPaid'),
        approvedAmount: document.getElementById('approvedAmount'),
        pendingAmount: document.getElementById('pendingAmount'),
        remainingAmount: document.getElementById('remainingAmount'),
        financialProgress: document.getElementById('financialProgress'),
        registeredHours: document.getElementById('registeredHours'),
        requiredHours: document.getElementById('requiredHours'),
        missingHours: document.getElementById('missingHours'),
        hoursProgress: document.getElementById('hoursProgress'),
        meetingsAttended: document.getElementById('meetingsAttended'),
        meetingsProgress: document.getElementById('meetingsProgress'),
        upcomingMeetings: document.getElementById('upcomingMeetings'),
        unitInfo: document.getElementById('unitInfo'),
        
        // Elementos de pagos
        uploadPaymentBtn: document.getElementById('uploadPaymentBtn'),
        uploadForm: document.getElementById('upload-form'),
        uploadPaymentForm: document.getElementById('uploadPaymentForm'),
        cancelUpload: document.getElementById('cancelUpload'),
        uploadArea: document.getElementById('uploadArea'),
        paymentFile: document.getElementById('payment_file'),
        paymentsList: document.getElementById('paymentsList'),
        paymentMessages: document.getElementById('paymentMessages'),
        
        // Elementos de horas
        addHoursBtn: document.getElementById('addHoursBtn'),
        hoursForm: document.getElementById('hours-form'),
        hoursFormElement: document.getElementById('hoursForm'),
        cancelHours: document.getElementById('cancelHours'),
        hoursList: document.getElementById('hoursList'),
        hoursMessages: document.getElementById('hoursMessages'),
        
        // Elementos de reuniones
        meetingsList: document.getElementById('meetingsList'),
        
        // Elementos de tareas
        taskList: document.getElementById('taskList'),
        addTaskBtn: document.getElementById('addTaskBtn')
    };
}

function setupEventListeners() {
    // Navegación principal
    PerfilApp.elements.navButtons.forEach(btn => {
        if (!btn.classList.contains('nav-toggle')) {
            btn.addEventListener('click', () => {
                const section = btn.getAttribute('data-section');
                showSection(section);
            });
        }
    });
    
    // Navegación con submenús
    PerfilApp.elements.navToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const menuKey = toggle.getAttribute('data-menu');
            toggleSubmenu(menuKey);
        });
    });
    
    // Subítems de navegación
    document.querySelectorAll('.nav-subitem').forEach(item => {
        item.addEventListener('click', () => {
            const section = item.getAttribute('data-section');
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
        PerfilApp.elements.uploadPaymentBtn.addEventListener('click', () => {
            if (!PerfilApp.currentUser.hasInitialPayment) {
                showMessage('paymentMessages', 'Debes completar el pago inicial antes de subir comprobantes', 'error');
                return;
            }
            showUploadForm();
        });
    }
    
    if (PerfilApp.elements.addHoursBtn) {
        PerfilApp.elements.addHoursBtn.addEventListener('click', () => {
            if (!PerfilApp.currentUser.hasInitialPayment) {
                showMessage('hoursMessages', 'Debes completar el pago inicial antes de registrar horas', 'error');
                return;
            }
            showHoursForm();
        });
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
    
    if (PerfilApp.elements.cancelUpload) {
        PerfilApp.elements.cancelUpload.addEventListener('click', hideUploadForm);
    }
    
    // Formulario de horas
    if (PerfilApp.elements.hoursFormElement) {
        PerfilApp.elements.hoursFormElement.addEventListener('submit', submitHoursForm);
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
    
    // Verificar si tiene pago inicial
    checkInitialPayment();
    
    // Inicializar datos
    initializeData();
    
    // Actualizar dashboard
    updateDashboard();
    
    // Marcar como cargado
    PerfilApp.state.isUserDataLoaded = true;
    
    console.log('Aplicación inicializada correctamente');
}

// === FUNCIONES DE PAGO INICIAL ===

function checkInitialPayment() {
    // Verificar si el usuario tiene pago inicial
    // En producción, esto vendría de la base de datos
    const hasInitialPayment = localStorage.getItem('hasInitialPayment') === 'true';
    
    PerfilApp.currentUser.hasInitialPayment = hasInitialPayment;
    
    if (!hasInitialPayment) {
        showInitialPaymentModal();
        disableAllActions();
    } else {
        enableAllActions();
    }
}

function showInitialPaymentModal() {
    if (PerfilApp.elements.initialPaymentModal) {
        PerfilApp.elements.initialPaymentModal.style.display = 'flex';
    }
    
    if (PerfilApp.elements.noAccessWarning) {
        PerfilApp.elements.noAccessWarning.style.display = 'flex';
    }
}

function handleInitialPayment() {
    // Cerrar modal
    if (PerfilApp.elements.initialPaymentModal) {
        PerfilApp.elements.initialPaymentModal.style.display = 'none';
    }
    
    // Mostrar formulario de pago
    showSection('payments');
    showUploadForm();
    
    // Mensaje especial para pago inicial
    showMessage('paymentMessages', 'Por favor, sube el comprobante de tu pago inicial de $50,000. Una vez aprobado, tendrás acceso completo al sistema.', 'info');
}

function disableAllActions() {
    // Deshabilitar botones
    const actionButtons = document.querySelectorAll('.btn-primary');
    actionButtons.forEach(btn => {
        if (btn.id !== 'uploadPaymentBtn') {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
        }
    });
    
    // Deshabilitar navegación excepto dashboard y pagos
    PerfilApp.elements.navButtons.forEach(btn => {
        const section = btn.getAttribute('data-section');
        if (section && section !== 'dashboard' && section !== 'payments') {
            btn.disabled = true;
            btn.style.opacity = '0.5';
        }
    });
}

function enableAllActions() {
    // Habilitar botones
    const actionButtons = document.querySelectorAll('.btn-primary');
    actionButtons.forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    });
    
    // Habilitar navegación
    PerfilApp.elements.navButtons.forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = '1';
    });
    
    // Ocultar advertencia
    if (PerfilApp.elements.noAccessWarning) {
        PerfilApp.elements.noAccessWarning.style.display = 'none';
    }
}

// Simular aprobación de pago inicial (para pruebas)
function approveInitialPayment() {
    localStorage.setItem('hasInitialPayment', 'true');
    PerfilApp.currentUser.hasInitialPayment = true;
    PerfilApp.currentUser.hasUnit = true;
    
    enableAllActions();
    updateDashboard();
    
    showMessage('paymentMessages', '¡Pago inicial aprobado! Ya puedes acceder a todas las funcionalidades del sistema.', 'success');
}

// === FUNCIONES DE NAVEGACIÓN ===

function showSection(sectionName) {
    // Verificar acceso si no tiene pago inicial
    if (!PerfilApp.currentUser.hasInitialPayment) {
        if (sectionName !== 'dashboard' && sectionName !== 'payments') {
            showMessage('paymentMessages', 'Debes completar el pago inicial para acceder a esta sección', 'error');
            return;
        }
    }
    
    // Ocultar todas las secciones
    PerfilApp.elements.sections.forEach(section => {
        section.classList.remove('active');
    });
    
    // Desactivar todos los botones de navegación
    PerfilApp.elements.navButtons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.querySelectorAll('.nav-subitem').forEach(item => {
        item.classList.remove('active');
    });
    
    // Mostrar la sección seleccionada
    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.add('active');
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

function toggleSubmenu(menuKey) {
    const submenu = document.getElementById(`${menuKey}-submenu`);
    const toggle = document.querySelector(`[data-menu="${menuKey}"]`);
    
    if (!submenu || !toggle) return;
    
    const isExpanded = PerfilApp.state.expandedMenus[menuKey];
    
    if (isExpanded) {
        submenu.style.display = 'none';
        toggle.classList.remove('expanded');
    } else {
        submenu.style.display = 'flex';
        toggle.classList.add('expanded');
    }
    
    PerfilApp.state.expandedMenus[menuKey] = !isExpanded;
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

// === FUNCIONES DE DASHBOARD ===

function updateDashboard() {
    updateFinancialInfo();
    updateHoursInfo();
    updateMeetingsInfo();
    updateUnitInfo();
}

function updateFinancialInfo() {
    const { monthlyFee, totalPaid, approvedPaid } = PerfilApp.currentUser;
    const pending = totalPaid - approvedPaid;
    const remaining = monthlyFee - approvedPaid;
    const progress = Math.min((approvedPaid / monthlyFee) * 100, 100);
    
    if (PerfilApp.elements.monthlyFee) {
        PerfilApp.elements.monthlyFee.textContent = `$${monthlyFee.toLocaleString()}`;
    }
    
    if (PerfilApp.elements.totalPaid) {
        PerfilApp.elements.totalPaid.textContent = `$${totalPaid.toLocaleString()}`;
    }
    
    if (PerfilApp.elements.approvedAmount) {
        PerfilApp.elements.approvedAmount.textContent = `$${approvedPaid.toLocaleString()}`;
    }
    
    if (PerfilApp.elements.pendingAmount) {
        PerfilApp.elements.pendingAmount.textContent = `$${pending.toLocaleString()}`;
    }
    
    if (PerfilApp.elements.remainingAmount) {
        PerfilApp.elements.remainingAmount.textContent = `$${remaining.toLocaleString()}`;
    }
    
    if (PerfilApp.elements.financialProgress) {
        PerfilApp.elements.financialProgress.style.width = `${progress}%`;
    }
}

function updateHoursInfo() {
    const { totalHoursMonth, requiredHours } = PerfilApp.currentUser;
    const missing = requiredHours - totalHoursMonth;
    const progress = Math.min((totalHoursMonth / requiredHours) * 100, 100);
    
    if (PerfilApp.elements.registeredHours) {
        PerfilApp.elements.registeredHours.textContent = `${totalHoursMonth} hrs`;
    }
    
    if (PerfilApp.elements.requiredHours) {
        PerfilApp.elements.requiredHours.textContent = `${requiredHours} hrs`;
    }
    
    if (PerfilApp.elements.missingHours) {
        PerfilApp.elements.missingHours.textContent = `${missing} hrs`;
    }
    
    if (PerfilApp.elements.hoursProgress) {
        PerfilApp.elements.hoursProgress.style.width = `${progress}%`;
    }
}

function updateMeetingsInfo() {
    const { meetingsAttended, requiredMeetings } = PerfilApp.currentUser;
    const progress = Math.min((meetingsAttended / requiredMeetings) * 100, 100);
    
    if (PerfilApp.elements.meetingsAttended) {
        PerfilApp.elements.meetingsAttended.textContent = `${meetingsAttended} / ${requiredMeetings}`;
    }
    
    if (PerfilApp.elements.meetingsProgress) {
        PerfilApp.elements.meetingsProgress.style.width = `${progress}%`;
    }
    
    // Renderizar próximas reuniones
    renderUpcomingMeetings();
}

function renderUpcomingMeetings() {
    if (!PerfilApp.elements.upcomingMeetings) return;
    
    const meetings = PerfilApp.mockData.upcomingMeetings;
    
    let html = '<h4>Próximas Reuniones</h4>';
    
    meetings.forEach(meeting => {
        const date = new Date(meeting.date);
        const formattedDate = date.toLocaleDateString('es-ES', { 
            day: '2-digit', 
            month: 'long', 
            year: 'numeric' 
        });
        
        html += `
            <div class="meeting-item">
                <div>
                    <div class="meeting-title">${meeting.title}</div>
                    <small class="meeting-date">${formattedDate}</small>
                </div>
                ${meeting.required ? '<span class="badge badge-required">Obligatoria</span>' : ''}
            </div>
        `;
    });
    
    PerfilApp.elements.upcomingMeetings.innerHTML = html;
}

function updateUnitInfo() {
    if (!PerfilApp.elements.unitInfo) return;
    
    if (PerfilApp.currentUser.hasUnit) {
        PerfilApp.elements.unitInfo.innerHTML = `
            <div class="stat-row">
                <span class="stat-label">Unidad asignada:</span>
                <span class="stat-value">A-204</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Estado:</span>
                <span class="badge badge-success">Activa</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Tipo:</span>
                <span class="stat-value">2 Dormitorios</span>
            </div>
        `;
    } else {
        PerfilApp.elements.unitInfo.innerHTML = `
            <div class="alert alert-info">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <div>
                    <strong>Sin unidad asignada</strong>
                    <p style="margin: 4px 0 0 0; fontSize: 14px;">
                        Una vez aprobado tu pago inicial, se te asignará una unidad habitacional.
                    </p>
                </div>
            </div>
        `;
    }
}

// === FUNCIONES DE TAREAS ===

function showAddTaskDialog() {
    if (!PerfilApp.currentUser.hasInitialPayment) {
        alert('Debes completar el pago inicial para agregar tareas');
        return;
    }
    
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
    
    // Event listeners
    const checkbox = taskElement.querySelector('.task-checkbox');
    const starBtn = taskElement.querySelector('.star-btn');
    const deleteBtn = taskElement.querySelector('.delete-btn');
    
    checkbox.addEventListener('change', () => toggleTask(task.id));
    starBtn.addEventListener('click', () => toggleFavorite(task.id));
    deleteBtn.addEventListener('click', () => deleteTask(task.id));
    
    taskContainer.appendChild(taskElement);
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
}

function deleteTask(taskId) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta tarea?')) return;
    
    PerfilApp.mockData.tasks = PerfilApp.mockData.tasks.filter(t => t.id != taskId);
    
    const taskElement = document.querySelector(`[data-id="${taskId}"]`);
    if (taskElement) {
        taskElement.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            taskElement.remove();
        }, 300);
    }
}

// === FUNCIONES DE PAGOS ===

function showUploadForm() {
    PerfilApp.elements.uploadForm.style.display = 'block';
    PerfilApp.elements.uploadForm.classList.add('animate-fadeIn');
    PerfilApp.state.formsVisible.upload = true;
    
    PerfilApp.elements.uploadForm.scrollIntoView({ behavior: 'smooth' });
}

function hideUploadForm() {
    PerfilApp.elements.uploadForm.style.display = 'none';
    PerfilApp.state.formsVisible.upload = false;
    
    if (PerfilApp.elements.uploadPaymentForm) {
        PerfilApp.elements.uploadPaymentForm.reset();
    }
    
    resetUploadArea();
}

function handleFileSelect(file) {
    if (!file) return;
    
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    if (!allowedTypes.includes(file.type)) {
        showMessage('paymentMessages', 'Tipo de archivo no válido. Use PDF, JPG o PNG.', 'error');
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        showMessage('paymentMessages', 'El archivo es demasiado grande. Máximo 5MB.', 'error');
        return;
    }
    
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
    const file = formData.get('payment_file');
    
    if (!file || !month || !year) {
        showMessage('paymentMessages', 'Por favor completa todos los campos requeridos', 'error');
        return;
    }
    
    // Simular envío
    setTimeout(() => {
        const newPayment = {
            id: Date.now(),
            month: month,
            year: year,
            amount: 22000,
            status: 'pending',
            date: new Date().toISOString().split('T')[0],
            description: formData.get('payment_description') || ''
        };
        
        PerfilApp.mockData.payments.push(newPayment);
        
        showMessage('paymentMessages', 'Comprobante subido exitosamente. Pendiente de aprobación.', 'success');
        hideUploadForm();
        
        renderPaymentCard(newPayment);
        
        // Si es el primer pago y no tiene pago inicial, simular aprobación automática
        if (!PerfilApp.currentUser.hasInitialPayment && PerfilApp.mockData.payments.length === 1) {
            setTimeout(() => {
                approveInitialPayment();
            }, 2000);
        }
        
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
                <span class="payment-amount">${payment.amount.toLocaleString()}</span>
                <span class="payment-date">${formatDate(payment.date)}</span>
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
    
    const today = new Date().toISOString().split('T')[0];
    const dateInput = PerfilApp.elements.hoursForm.querySelector('input[name="work_date"]');
    if (dateInput && !dateInput.value) {
        dateInput.value = today;
    }
    
    PerfilApp.elements.hoursForm.scrollIntoView({ behavior: 'smooth' });
}

function hideHoursForm() {
    PerfilApp.elements.hoursForm.style.display = 'none';
    PerfilApp.state.formsVisible.hours = false;
    
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
    
    if (!workDate || !hours || !description || !workType) {
        showMessage('hoursMessages', 'Por favor completa todos los campos requeridos', 'error');
        return;
    }
    
    if (hours < 0.5 || hours > 24) {
        showMessage('hoursMessages', 'Las horas deben estar entre 0.5 y 24', 'error');
        return;
    }
    
    const selectedDate = new Date(workDate);
    const today = new Date();
    today.setHours(23, 59, 59, 999);
    
    if (selectedDate > today) {
        showMessage('hoursMessages', 'No puedes registrar horas de fechas futuras', 'error');
        return;
    }
    
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
        
        // Actualizar total de horas
        PerfilApp.currentUser.totalHoursMonth += parseFloat(hours);
        
        showMessage('hoursMessages', 'Horas registradas exitosamente', 'success');
        hideHoursForm();
        
        updateDashboard();
        renderHoursCard(newHours);
        
    }, 1000);
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
    
    const hours = PerfilApp.mockData.hours.find(h => h.id == hoursId);
    if (hours) {
        PerfilApp.currentUser.totalHoursMonth -= hours.hours;
    }
    
    PerfilApp.mockData.hours = PerfilApp.mockData.hours.filter(h => h.id != hoursId);
    
    const hoursCard = Array.from(document.querySelectorAll('.hours-card')).find(card => 
        card.querySelector(`button[onclick="deleteHours(${hoursId})"]`)
    );
    
    if (hoursCard) {
        hoursCard.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            hoursCard.remove();
            updateDashboard();
        }, 300);
    }
}

// === FUNCIONES DE REUNIONES ===

function renderMeetingsList() {
    if (!PerfilApp.elements.meetingsList) return;
    
    const meetings = PerfilApp.mockData.upcomingMeetings;
    
    let html = '';
    
    meetings.forEach(meeting => {
        const date = new Date(meeting.date);
        const formattedDate = date.toLocaleDateString('es-ES', { 
            day: '2-digit', 
            month: 'long', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        html += `
            <div class="meeting-card ${meeting.attended ? 'attended' : ''}">
                <div class="meeting-card-header">
                    <h3>${meeting.title}</h3>
                    ${meeting.required ? '<span class="badge badge-required">Obligatoria</span>' : ''}
                </div>
                <p class="meeting-date">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    ${formattedDate}
                </p>
                <div class="meeting-actions">
                    ${meeting.attended 
                        ? '<span class="badge badge-success">Asistió</span>' 
                        : `<button class="btn btn-secondary small" onclick="markAttendance(${meeting.id})">Confirmar Asistencia</button>`
                    }
                </div>
            </div>
        `;
    });
    
    PerfilApp.elements.meetingsList.innerHTML = html;
}

function markAttendance(meetingId) {
    const meeting = PerfilApp.mockData.upcomingMeetings.find(m => m.id === meetingId);
    if (!meeting) return;
    
    meeting.attended = true;
    PerfilApp.currentUser.meetingsAttended++;
    
    updateDashboard();
    renderMeetingsList();
    
    showMessage('hoursMessages', 'Asistencia registrada exitosamente', 'success');
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
    
    const messageElement = document.createElement('div');
    messageElement.className = `alert ${alertClass} animate-slideIn`;
    messageElement.innerHTML = `
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>${message}</div>
    `;
    
    container.innerHTML = '';
    container.appendChild(messageElement);
    
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

function initializeData() {
    // Configurar nombre de usuario
    const userNameDisplay = document.getElementById('userNameDisplay');
    if (userNameDisplay) {
        userNameDisplay.textContent = PerfilApp.currentUser.name;
    }
    
    // Renderizar tareas iniciales
    PerfilApp.mockData.tasks.forEach(task => renderTask(task));
    
    // Renderizar pagos iniciales
    PerfilApp.mockData.payments.forEach(payment => renderPaymentCard(payment));
    
    // Renderizar horas iniciales
    PerfilApp.mockData.hours.forEach(hours => renderHoursCard(hours));
    
    // Renderizar reuniones
    renderMeetingsList();
}

function hideAllForms() {
    hideUploadForm();
    hideHoursForm();
}

function handleKeyboardShortcuts(event) {
    if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
        return;
    }
    
    if (event.ctrlKey || event.metaKey) {
        switch (event.key) {
            case '1':
                event.preventDefault();
                showSection('dashboard');
                break;
            case '2':
                event.preventDefault();
                showSection('payments');
                break;
            case '3':
                event.preventDefault();
                showSection('hours');
                break;
            case '4':
                event.preventDefault();
                showSection('unit');
                break;
            case '5':
                event.preventDefault();
                showSection('tasks');
                break;
        }
    }
    
    if (event.key === 'Escape') {
        hideAllForms();
        closeProfileMenu();
    }
}

// === FUNCIONES GLOBALES ===

function logout() {
    if (confirm('¿Estás seguro que quieres cerrar sesión?')) {
        localStorage.clear();
        window.location.href = 'index.php';
    }
}

function goToAdmin() {
    window.location.href = 'BACKOFFICE/admin.php';
}

function openChat() {
    alert('Función de chat en desarrollo');
}

// Hacer funciones disponibles globalmente
window.logout = logout;
window.goToAdmin = goToAdmin;
window.openChat = openChat;
window.viewPayment = viewPayment;
window.deleteHours = deleteHours;
window.handleInitialPayment = handleInitialPayment;
window.approveInitialPayment = approveInitialPayment;
window.markAttendance = markAttendance;

// === DESARROLLO Y DEBUG ===

if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    window.PerfilApp = PerfilApp;
    
    console.log('🔧 Modo desarrollo activado');
    console.log('Comandos disponibles:');
    console.log('- approveInitialPayment() - Aprobar pago inicial');
    console.log('- PerfilApp.currentUser - Ver datos del usuario');
    console.log('- PerfilApp.mockData - Ver datos mock');
}

console.log('✅ Urban Coop Perfil - Sistema cargado correctamente');