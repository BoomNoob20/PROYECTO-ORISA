// Variables globales
let currentSection = 'info';

// Función para mostrar secciones
function showSection(sectionName) {
    // Ocultar todas las secciones
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });

    // Remover clase active de todos los nav links
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });

    document.querySelectorAll('.nav-sub-link').forEach(link => {
        link.classList.remove('active');
    });

    // Mostrar la sección seleccionada
    const section = document.getElementById(sectionName + '-section');
    if (section) {
        section.classList.add('active');
    }

    // Activar el nav link correspondiente
    const navLink = document.getElementById(sectionName + '-nav');
    if (navLink) {
        navLink.classList.add('active');
    }

    // Actualizar título de la página
    const titles = {
        'info': 'Dashboard',
        'users': 'Trabajadores en Espera',
        'payments': 'Pagos Pendientes',
        'payments-late': 'Pagos Atrasados',
        'payments-remuneration': 'Remuneración de Horas',
        'hours': 'Horas Pendientes',
        'hours-history': 'Historial de Horas',
        'meetings': 'Gestionar Reuniones',
        'units-assign': 'Asignar Unidades',
        'units-create': 'Crear Nueva Unidad',
        'debug': 'Información Debug'
    };

    const titleElement = document.getElementById('page-title');
    if (titleElement && titles[sectionName]) {
        titleElement.textContent = titles[sectionName];
    }

    currentSection = sectionName;

    // Cerrar sidebar en móvil
    if (window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.remove('open');
    }
}

// Función para toggle accordion
function toggleAccordion(header) {
    const content = header.nextElementSibling;
    const arrow = header.querySelector('.accordion-arrow');
    const isOpen = content.classList.contains('open');

    // Cerrar todos los accordions
    document.querySelectorAll('.nav-accordion-content').forEach(c => {
        c.classList.remove('open');
    });

    document.querySelectorAll('.accordion-arrow').forEach(a => {
        a.classList.remove('rotated');
    });

    document.querySelectorAll('.nav-accordion-header').forEach(h => {
        h.classList.remove('active');
    });

    // Abrir el accordion clickeado si estaba cerrado
    if (!isOpen) {
        content.classList.add('open');
        arrow.classList.add('rotated');
        header.classList.add('active');
    }
}

// Función para mostrar alertas
function showAlert(message, type) {
    const alert = document.getElementById(type + '-alert');
    if (alert) {
        alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
        alert.style.display = 'block';
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
        alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// Función para agregar estado de carga a botones
function addLoadingState(button) {
    if (button) {
        button.disabled = true;
        const originalContent = button.innerHTML;
        button.innerHTML = '<div class="spinner"></div> Procesando...';
        return function removeLoadingState() {
            button.disabled = false;
            button.innerHTML = originalContent;
        };
    }
    return function () { };
}

// Función para procesar usuarios
function processUser(userId, action, button) {
    const removeLoading = addLoadingState(button);
    const actionText = action === 'approve' ? 'approve_user' : 'reject_user';

    fetch('admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${actionText}&user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        removeLoading();
        if (data.success) {
            showAlert(data.message, 'success');
            const card = document.getElementById(`user-card-${userId}`);
            if (card) {
                card.style.transform = 'translateX(100%)';
                card.style.opacity = '0';
                setTimeout(() => {
                    card.remove();
                    location.reload();
                }, 300);
            }
        } else {
            showAlert(data.message || 'Error al procesar usuario', 'error');
        }
    })
    .catch(error => {
        removeLoading();
        showAlert('Error de conexión', 'error');
    });
}

// Función para procesar pagos
function processPayment(paymentId, action, button) {
    const removeLoading = addLoadingState(button);
    const actionText = action === 'approve' ? 'approve_payment' : 'reject_payment';

    fetch('admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${actionText}&payment_id=${paymentId}`
    })
    .then(response => response.json())
    .then(data => {
        removeLoading();
        if (data.success) {
            showAlert(data.message, 'success');
            const card = document.getElementById(`payment-card-${paymentId}`);
            if (card) {
                card.style.transform = 'translateX(100%)';
                card.style.opacity = '0';
                setTimeout(() => {
                    card.remove();
                    location.reload();
                }, 300);
            }
        } else {
            showAlert(data.message || 'Error al procesar comprobante', 'error');
        }
    })
    .catch(error => {
        removeLoading();
        showAlert('Error de conexión', 'error');
    });
}

// Función para procesar horas
function processHours(hoursId, action, button) {
    const removeLoading = addLoadingState(button);
    const actionText = action === 'approve' ? 'approve_hours' : 'reject_hours';

    fetch('admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${actionText}&hours_id=${hoursId}`
    })
    .then(response => response.json())
    .then(data => {
        removeLoading();
        if (data.success) {
            showAlert(data.message, 'success');
            const card = document.getElementById(`hours-card-${hoursId}`);
            if (card) {
                card.style.transform = 'translateX(100%)';
                card.style.opacity = '0';
                setTimeout(() => {
                    card.remove();
                    location.reload();
                }, 300);
            }
        } else {
            showAlert(data.message || 'Error al procesar horas', 'error');
        }
    })
    .catch(error => {
        removeLoading();
        showAlert('Error de conexión', 'error');
    });
}

// Función para toggle checkbox
function toggleCheckbox(checkbox) {
    const isChecked = checkbox.style.backgroundColor === 'rgb(211, 47, 47)';
    if (isChecked) {
        checkbox.style.backgroundColor = '';
        checkbox.innerHTML = '';
        checkbox.style.borderColor = '#d0d0d3';
    } else {
        checkbox.style.backgroundColor = '#d32f2f';
        checkbox.style.borderColor = '#d32f2f';
        checkbox.innerHTML = '<i class="fas fa-check" style="color: white; font-size: 12px;"></i>';
    }
}

// Función para toggle sidebar móvil
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

// Función para ir al perfil
function goToProfile() {
    const userData = sessionStorage.getItem('user_data');
    if (!userData) {
        alert('Error: No se encontró información de sesión');
        document.location = '../loginLP.php';
        return;
    }
    window.location.href = '../perfil.php';
}

// Función para mostrar detalles de unidad
function showUnitDetails(unitId) {
    const select = document.getElementById('unit-selector');
    const selectedOption = select.options[select.selectedIndex];
    const detailsDiv = document.getElementById('unit-details');

    if (unitId && selectedOption) {
        const cuartos = selectedOption.getAttribute('data-cuartos');
        const banos = selectedOption.getAttribute('data-banos');
        const tamano = selectedOption.getAttribute('data-tamano');
        const capacidad = selectedOption.getAttribute('data-capacidad');
        const tipo = selectedOption.getAttribute('data-tipo');
        const bloque = selectedOption.getAttribute('data-bloque');

        document.getElementById('detail-cuartos').textContent = cuartos;
        document.getElementById('detail-banos').textContent = banos;
        document.getElementById('detail-tamano').textContent = tamano;
        document.getElementById('detail-capacidad').textContent = capacidad;
        document.getElementById('detail-tipo').textContent = tipo.charAt(0).toUpperCase() + tipo.slice(1);
        document.getElementById('detail-bloque').textContent = bloque || 'N/A';

        detailsDiv.style.display = 'block';
    } else {
        detailsDiv.style.display = 'none';
    }
}

// Event Listeners cuando el DOM está cargado
document.addEventListener('DOMContentLoaded', function () {
    // Verificar sesión
    const userData = sessionStorage.getItem('user_data');
    if (!userData) {
        alert('Debes iniciar sesión');
        document.location = 'loginLP.php';
        return;
    }

    const user = JSON.parse(userData);
    if (user.is_admin != 1) {
        alert('No tienes permisos de administrador');
        document.location = 'perfil.php';
        return;
    }

    // Actualizar nombre de usuario en header
    const userMenu = document.querySelector('.user-menu span');
    if (userMenu) {
        userMenu.textContent = user.name + ' ' + user.surname;
    }

    // Mostrar sección info por defecto
    showSection('info');

    // Handler para formulario de reuniones
    const meetingForm = document.getElementById('meeting-form');
    if (meetingForm) {
        meetingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create_meeting');

            fetch('admin.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    this.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Error al crear reunión', 'error');
                console.error('Error:', error);
            });
        });
    }

    // Handler para formulario de asignación de unidades
    const unitAssignForm = document.getElementById('unit-assign-form');
    if (unitAssignForm) {
        unitAssignForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'assign_unit');

            fetch('admin.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    this.reset();
                    document.getElementById('unit-details').style.display = 'none';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Error al asignar unidad', 'error');
                console.error('Error:', error);
            });
        });
    }

    // Handler para formulario de creación de unidades
    const unitCreateForm = document.getElementById('unit-create-form');
    if (unitCreateForm) {
        unitCreateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create_unit');

            fetch('admin.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    this.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Error al crear unidad', 'error');
                console.error('Error:', error);
            });
        });
    }
});