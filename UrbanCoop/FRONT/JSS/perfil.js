// ===== URBAN COOP - PERFIL JAVASCRIPT CON BASE DE DATOS =====
// Sistema de gestión cooperativa con pago inicial y dashboard

'use strict';

// === CONFIGURACIÓN DE RUTAS ===
const API_URL = '../APIS/perfil_api.php';

// Obtener parámetros de la URL
function getUrlParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        user_id: params.get('user_id') || '',
        verify: params.get('verify') || ''
    };
}

// === VARIABLES GLOBALES ===
const PerfilApp = {
    // Datos del usuario actual
    currentUser: {
        id: 0,
        name: 'Usuario',
        is_admin: false,
        hasInitialPayment: false,
        hasUnit: false,
        monthlyFee: 22000,
        totalPaid: 0,
        approvedPaid: 0,
        requiredHours: 20,
        totalHoursMonth: 0,
        meetingsAttended: 0,
        requiredMeetings: 3
    },
    
    // Estado de la aplicación
    state: {
        isUserDataLoaded: false,
        activeSection: 'dashboard',
        bannerClosed: false,
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
    
    // Datos
    data: {
        payments: [],
        hours: []
    }
};

// === INICIALIZACIÓN ===
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== URBAN COOP PERFIL INIT ===');
    console.log('API URL:', API_URL);
    console.log('Parámetros URL:', getUrlParams());
    
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
        
        // Banner y CTA de pago inicial
        noAccessWarning: document.getElementById('noAccessWarning'),
        initialPaymentAction: document.getElementById('initialPaymentAction'),
        
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
        hoursMessages: document.getElementById('hoursMessages')
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
    
    // Modal de pago inicial - cerrar al hacer clic en el overlay
    if (PerfilApp.elements.initialPaymentModal) {
        PerfilApp.elements.initialPaymentModal.addEventListener('click', (event) => {
            // Solo cerrar si el clic fue en el overlay, no en el modal
            if (event.target === PerfilApp.elements.initialPaymentModal) {
                closeInitialPaymentModal();
            }
        });
    }
    
    // Botones de acción
    if (PerfilApp.elements.uploadPaymentBtn) {
        PerfilApp.elements.uploadPaymentBtn.addEventListener('click', () => {
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

async function initializeApp() {
    // Ocultar pantalla de carga después de cargar datos
    setTimeout(async () => {
        try {
            // Cargar datos del usuario
            await loadUserData();
            
            // Verificar pago inicial
            await checkInitialPayment();
            
            // Cargar pagos
            await loadPayments();
            
            // Cargar horas
            await loadHours();
            
            // Actualizar dashboard
            updateDashboard();
            
            console.log('✅ Aplicación inicializada correctamente');
        } catch (error) {
            console.error('❌ Error al inicializar:', error);
        } finally {
            // Ocultar loading y mostrar app
            hideLoadingScreen();
            PerfilApp.elements.mainApp.style.display = 'block';
            
            // Marcar como cargado
            PerfilApp.state.isUserDataLoaded = true;
        }
    }, 500);
}

// === FUNCIONES DE API ===

async function loadUserData() {
    try {
        const params = getUrlParams();
        const url = `${API_URL}?action=get_user_info&user_id=${params.user_id}&verify=${params.verify}`;
        
        console.log('📡 Cargando datos de usuario desde:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        
        console.log('📦 Datos recibidos:', data);
        
        if (data.success) {
            PerfilApp.currentUser.id = data.user.id;
            PerfilApp.currentUser.name = data.user.name;
            PerfilApp.currentUser.is_admin = data.user.is_admin;
            PerfilApp.currentUser.hasInitialPayment = data.user.hasInitialPayment;
            PerfilApp.currentUser.hasUnit = data.user.hasUnit;
            PerfilApp.currentUser.totalPaid = data.stats.pagos.total_pagado;
            PerfilApp.currentUser.approvedPaid = data.stats.pagos.aprobado;
            PerfilApp.currentUser.totalHoursMonth = data.stats.horas.total;
            
            // Configurar nombre de usuario
            const userNameDisplay = document.getElementById('userNameDisplay');
            if (userNameDisplay) {
                userNameDisplay.textContent = PerfilApp.currentUser.name;
            }
            
            console.log('✅ Datos de usuario cargados');
        } else {
            console.error('❌ Error en respuesta:', data.error);
        }
    } catch (error) {
        console.error('❌ Error cargando datos del usuario:', error);
    }
}

async function checkInitialPayment() {
    try {
        const params = getUrlParams();
        const url = `${API_URL}?action=check_initial_payment&user_id=${params.user_id}&verify=${params.verify}`;
        
        console.log('📡 Verificando pago inicial desde:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        
        console.log('📦 Estado de pago inicial:', data);
        
        if (data.success) {
            PerfilApp.currentUser.hasInitialPayment = data.has_initial_payment;
            
            if (!data.has_initial_payment) {
                console.log('⚠️ Usuario sin pago inicial');
                showInitialPaymentModal();
                showInitialPaymentBanner();
                showInitialPaymentCTA();
                disableAllActions();
            } else {
                console.log('✅ Usuario con pago inicial aprobado');
                enableAllActions();
                hideInitialPaymentBanner();
                hideInitialPaymentCTA();
            }
        }
    } catch (error) {
        console.error('❌ Error verificando pago inicial:', error);
    }
}

async function loadPayments() {
    try {
        const params = getUrlParams();
        const url = `${API_URL}?action=get_payments&user_id=${params.user_id}&verify=${params.verify}`;
        
        console.log('📡 Cargando pagos desde:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        
        console.log('📦 Pagos recibidos:', data);
        
        if (data.success) {
            PerfilApp.data.payments = data.payments;
            
            // Limpiar lista
            if (PerfilApp.elements.paymentsList) {
                PerfilApp.elements.paymentsList.innerHTML = '';
            }
            
            // Renderizar cada pago
            data.payments.forEach(payment => {
                renderPaymentCard(payment);
            });
            
            console.log(`✅ ${data.payments.length} pagos cargados`);
        }
    } catch (error) {
        console.error('❌ Error cargando pagos:', error);
    }
}

async function loadHours() {
    try {
        const params = getUrlParams();
        const url = `${API_URL}?action=get_hours&user_id=${params.user_id}&verify=${params.verify}`;
        
        console.log('📡 Cargando horas desde:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        
        console.log('📦 Horas recibidas:', data);
        
        if (data.success) {
            PerfilApp.data.hours = data.hours;
            
            // Limpiar lista
            if (PerfilApp.elements.hoursList) {
                PerfilApp.elements.hoursList.innerHTML = '';
            }
            
            // Renderizar cada registro
            data.hours.forEach(hours => {
                renderHoursCard(hours);
            });
            
            console.log(`✅ ${data.hours.length} registros de horas cargados`);
        }
    } catch (error) {
        console.error('❌ Error cargando horas:', error);
    }
}

// === FUNCIONES DE PAGO INICIAL ===

function showInitialPaymentModal() {
    if (PerfilApp.elements.initialPaymentModal) {
        PerfilApp.elements.initialPaymentModal.style.display = 'flex';
        setTimeout(() => {
            const modal = PerfilApp.elements.initialPaymentModal.querySelector('.modal');
            if (modal) {
                modal.style.animation = 'slideUp 0.3s ease';
            }
        }, 10);
    }
}

function closeInitialPaymentModal() {
    console.log('🔒 Cerrando modal de pago inicial');
    if (PerfilApp.elements.initialPaymentModal) {
        const modal = PerfilApp.elements.initialPaymentModal.querySelector('.modal');
        if (modal) {
            modal.style.animation = 'slideDown 0.3s ease';
        }
        setTimeout(() => {
            PerfilApp.elements.initialPaymentModal.style.display = 'none';
        }, 300);
    }
}

function showInitialPaymentBanner() {
    if (PerfilApp.elements.noAccessWarning && !PerfilApp.state.bannerClosed) {
        PerfilApp.elements.noAccessWarning.style.display = 'flex';
    }
}

function hideInitialPaymentBanner() {
    if (PerfilApp.elements.noAccessWarning) {
        PerfilApp.elements.noAccessWarning.style.display = 'none';
    }
}

function showInitialPaymentCTA() {
    if (PerfilApp.elements.initialPaymentAction) {
        PerfilApp.elements.initialPaymentAction.style.display = 'block';
    }
}

function hideInitialPaymentCTA() {
    if (PerfilApp.elements.initialPaymentAction) {
        PerfilApp.elements.initialPaymentAction.style.display = 'none';
    }
}

function closeBanner() {
    PerfilApp.state.bannerClosed = true;
    const banner = PerfilApp.elements.noAccessWarning;
    if (banner) {
        banner.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            banner.style.display = 'none';
        }, 300);
    }
}

function handleInitialPayment() {
    console.log('🔄 Iniciando proceso de pago inicial');
    
    // Cerrar modal primero
    closeInitialPaymentModal();
    
    // Pequeño delay para que se vea la transición del modal
    setTimeout(() => {
        // Cambiar a la sección de pagos
        showSection('payments');
        
        // Otro pequeño delay para mostrar el formulario
        setTimeout(() => {
            showUploadForm();
            
            // Mensaje especial para pago inicial
            showMessage('paymentMessages', 
                '💰 PAGO INICIAL: Sube el comprobante de tu pago inicial de $50,000. Una vez aprobado, tendrás acceso completo al sistema.', 
                'info'
            );
            
            // Scroll suave al formulario
            if (PerfilApp.elements.uploadForm) {
                PerfilApp.elements.uploadForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 300);
    }, 300);
}

function disableAllActions() {
    // Deshabilitar botones excepto los de pago
    const actionButtons = document.querySelectorAll('.btn-primary');
    actionButtons.forEach(btn => {
        if (btn.id !== 'uploadPaymentBtn' && !btn.onclick?.toString().includes('handleInitialPayment')) {
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
    
    document.querySelectorAll('.nav-subitem').forEach(item => {
        const section = item.getAttribute('data-section');
        if (section && section !== 'payments') {
            item.disabled = true;
            item.style.opacity = '0.5';
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
    
    document.querySelectorAll('.nav-subitem').forEach(item => {
        item.disabled = false;
        item.style.opacity = '1';
    });
}

// === FUNCIONES DE NAVEGACIÓN ===

function showSection(sectionName) {
    // Verificar acceso si no tiene pago inicial
    if (!PerfilApp.currentUser.hasInitialPayment) {
        if (sectionName !== 'dashboard' && sectionName !== 'payments') {
            showMessage('paymentMessages', '⚠️ Debes completar el pago inicial para acceder a esta sección', 'warning');
            showSection('dashboard');
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
    updateUnitInfo();
}

function updateFinancialInfo() {
    const { monthlyFee, totalPaid, approvedPaid } = PerfilApp.currentUser;
    const pending = totalPaid - approvedPaid;
    const remaining = Math.max(monthlyFee - approvedPaid, 0);
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
    const missing = Math.max(requiredHours - totalHoursMonth, 0);
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
                    <p style="margin: 4px 0 0 0; font-size: 14px;">
                        Una vez aprobado tu pago inicial, se te asignará una unidad habitacional.
                    </p>
                </div>
            </div>
        `;
    }
}

// === FUNCIONES DE PAGOS ===

function showUploadForm() {
    if (PerfilApp.elements.uploadForm) {
        PerfilApp.elements.uploadForm.style.display = 'block';
        PerfilApp.elements.uploadForm.classList.add('animate-fadeIn');
        PerfilApp.state.formsVisible.upload = true;
        
        PerfilApp.elements.uploadForm.scrollIntoView({ behavior: 'smooth' });
    }
}

function hideUploadForm() {
    if (PerfilApp.elements.uploadForm) {
        PerfilApp.elements.uploadForm.style.display = 'none';
        PerfilApp.state.formsVisible.upload = false;
    }
    
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
        uploadText.textContent = `✓ Archivo seleccionado: ${file.name}`;
        uploadText.style.color = '#4caf50';
        uploadText.style.fontWeight = '600';
    }
}

function resetUploadArea() {
    const uploadText = PerfilApp.elements.uploadArea?.querySelector('.upload-text');
    if (uploadText) {
        uploadText.textContent = 'Arrastra y suelta tu archivo aquí';
        uploadText.style.color = '';
        uploadText.style.fontWeight = '';
    }
    
    if (PerfilApp.elements.paymentFile) {
        PerfilApp.elements.paymentFile.value = '';
    }
}

async function submitPaymentForm(event) {
    event.preventDefault();
    
    console.log('📤 Enviando formulario de pago');
    
    const formData = new FormData(event.target);
    const month = formData.get('payment_month');
    const year = formData.get('payment_year');
    const file = formData.get('payment_file');
    
    console.log('Datos del formulario:', { month, year, fileName: file?.name });
    
    if (!file || !file.name || !month || !year) {
        showMessage('paymentMessages', 'Por favor completa todos los campos requeridos', 'error');
        return;
    }
    
    // Agregar acción
    formData.append('action', 'upload_payment');
    
    // Mostrar loading
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span style="display: flex; align-items: center; gap: 8px;"><div class="spinner" style="width: 16px; height: 16px; border-width: 2px;"></div>Subiendo...</span>';
    submitBtn.disabled = true;
    
    try {
        const params = getUrlParams();
        const url = `${API_URL}?user_id=${params.user_id}&verify=${params.verify}`;
        
        console.log('📡 Enviando a:', url);
        
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();console.log('📦 Respuesta del servidor:', data);
        
        if (data.success) {
            showMessage('paymentMessages', '✅ ' + data.message, 'success');
            hideUploadForm();
            
            // Recargar pagos
            await loadPayments();
            
            // Si es el primer pago, mostrar mensaje especial
            if (data.is_initial_payment) {
                showMessage('paymentMessages', '⏳ Tu pago inicial está siendo procesado. Será revisado por un administrador.', 'info');
            }
            
            // Actualizar datos
            await loadUserData();
            await checkInitialPayment();
            updateDashboard();
        } else {
            showMessage('paymentMessages', '❌ ' + data.error, 'error');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        showMessage('paymentMessages', '❌ Error al subir el comprobante. Verifica tu conexión.', 'error');
    } finally {
        // Restaurar botón
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

function renderPaymentCard(payment) {
    if (!PerfilApp.elements.paymentsList) return;
    
    const monthNames = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    
    const paymentCard = document.createElement('div');
    paymentCard.className = 'payment-card animate-slideIn';
    paymentCard.innerHTML = `
        <div class="payment-info">
            <h3 class="payment-title">Comprobante ${monthNames[parseInt(payment.payment_month)]} ${payment.payment_year}</h3>
            <div class="payment-details">
                <span class="payment-amount">$${(payment.monto || 22000).toLocaleString()}</span>
                <span class="payment-date">${formatDate(payment.created_at)}</span>
            </div>
            ${payment.description ? `<p style="margin-top: 8px; font-size: 13px; color: #666;">${payment.description}</p>` : ''}
        </div>
        <div class="payment-actions">
            <span class="payment-status ${payment.status}">${getStatusText(payment.status)}</span>
        </div>
    `;
    
    PerfilApp.elements.paymentsList.appendChild(paymentCard);
}

function getStatusText(status) {
    const statusMap = {
        'pending': 'Pendiente',
        'approved': 'Aprobado',
        'rejected': 'Rechazado'
    };
    return statusMap[status] || status;
}

// === FUNCIONES DE HORAS ===

function showHoursForm() {
    if (PerfilApp.elements.hoursForm) {
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
}

function hideHoursForm() {
    if (PerfilApp.elements.hoursForm) {
        PerfilApp.elements.hoursForm.style.display = 'none';
        PerfilApp.state.formsVisible.hours = false;
    }
    
    if (PerfilApp.elements.hoursFormElement) {
        PerfilApp.elements.hoursFormElement.reset();
    }
}

async function submitHoursForm(event) {
    event.preventDefault();
    
    console.log('📤 Enviando formulario de horas');
    
    const formData = new FormData(event.target);
    const workDate = formData.get('work_date');
    const hours = formData.get('hours_worked');
    const description = formData.get('description');
    const workType = formData.get('work_type');
    
    console.log('Datos del formulario:', { workDate, hours, description, workType });
    
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
    
    // Agregar acción
    formData.append('action', 'register_hours');
    
    // Mostrar loading
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span style="display: flex; align-items: center; gap: 8px;"><div class="spinner" style="width: 16px; height: 16px; border-width: 2px;"></div>Registrando...</span>';
    submitBtn.disabled = true;
    
    try {
        const params = getUrlParams();
        const url = `${API_URL}?user_id=${params.user_id}&verify=${params.verify}`;
        
        console.log('📡 Enviando a:', url);
        
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        console.log('📦 Respuesta del servidor:', data);
        
        if (data.success) {
            showMessage('hoursMessages', '✅ ' + data.message, 'success');
            hideHoursForm();
            
            // Recargar horas
            await loadHours();
            
            // Actualizar datos
            await loadUserData();
            updateDashboard();
        } else {
            showMessage('hoursMessages', '❌ ' + data.error, 'error');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        showMessage('hoursMessages', '❌ Error al registrar las horas. Verifica tu conexión.', 'error');
    } finally {
        // Restaurar botón
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

function renderHoursCard(hoursData) {
    if (!PerfilApp.elements.hoursList) return;
    
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
    hoursCard.setAttribute('data-hours-id', hoursData.id);
    hoursCard.innerHTML = `
        <div class="hours-info">
            <h3 class="hours-title">${formatDate(hoursData.work_date)} - ${hoursData.hours_worked} horas</h3>
            <div class="hours-type">${typeLabels[hoursData.work_type] || hoursData.work_type}</div>
            <p class="hours-description">${hoursData.description}</p>
            <small class="hours-date">Registrado el ${formatDateTime(hoursData.created_at)}</small>
        </div>
        <div class="hours-actions">
            <button class="action-button danger small" onclick="deleteHours(${hoursData.id})">Eliminar</button>
        </div>
    `;
    
    PerfilApp.elements.hoursList.appendChild(hoursCard);
}

async function deleteHours(hoursId) {
    if (!confirm('¿Estás seguro de que quieres eliminar este registro de horas?')) return;
    
    console.log('🗑️ Eliminando registro de horas:', hoursId);
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_hours');
        formData.append('hours_id', hoursId);
        
        const params = getUrlParams();
        const url = `${API_URL}?user_id=${params.user_id}&verify=${params.verify}`;
        
        console.log('📡 Enviando a:', url);
        
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        console.log('📦 Respuesta del servidor:', data);
        
        if (data.success) {
            // Animar y eliminar card
            const hoursCard = document.querySelector(`[data-hours-id="${hoursId}"]`);
            if (hoursCard) {
                hoursCard.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => {
                    hoursCard.remove();
                }, 300);
            }
            
            showMessage('hoursMessages', '✅ Registro eliminado', 'success');
            
            // Actualizar datos
            await loadUserData();
            updateDashboard();
        } else {
            showMessage('hoursMessages', '❌ ' + data.error, 'error');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        showMessage('hoursMessages', '❌ Error al eliminar el registro', 'error');
    }
}

// === FUNCIONES DE UTILIDAD ===

function hideLoadingScreen() {
    if (PerfilApp.elements.loadingScreen) {
        PerfilApp.elements.loadingScreen.style.opacity = '0';
        setTimeout(() => {
            PerfilApp.elements.loadingScreen.style.display = 'none';
        }, 300);
    }
}

function showMessage(containerId, message, type = 'info') {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const alertClass = `alert-${type}`;
    const icons = {
        'info': '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>',
        'success': '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
        'error': '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
        'warning': '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>'
    };
    
    const messageElement = document.createElement('div');
    messageElement.className = `alert ${alertClass} animate-slideIn`;
    messageElement.innerHTML = `
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            ${icons[type] || icons['info']}
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
        }
    }
    
    if (event.key === 'Escape') {
        hideAllForms();
        closeProfileMenu();
        closeInitialPaymentModal();
    }
}

// === FUNCIONES GLOBALES ===

function logout() {
    if (confirm('¿Estás seguro que quieres cerrar sesión?')) {
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
window.deleteHours = deleteHours;
window.handleInitialPayment = handleInitialPayment;
window.closeInitialPaymentModal = closeInitialPaymentModal;
window.closeBanner = closeBanner;

// === DESARROLLO Y DEBUG ===

if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    window.PerfilApp = PerfilApp;
    
    console.log('🔧 Modo desarrollo activado');
    console.log('Comandos disponibles:');
    console.log('- PerfilApp.currentUser - Ver datos del usuario');
    console.log('- PerfilApp.data - Ver datos cargados');
    console.log('API URL:', API_URL);
}

console.log('✅ Urban Coop Perfil - Sistema cargado correctamente');