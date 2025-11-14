const themeToggle = document.getElementById('themeToggle');
const body = document.body;
const sunIcon = document.querySelector('.sun-icon');
const moonIcon = document.querySelector('.moon-icon');

// Get saved theme from memory (since we can't use localStorage in artifacts)
let currentTheme = 'light';

// Function to toggle theme
function toggleTheme() {
    currentTheme = currentTheme === 'light' ? 'dark' : 'light';
    body.setAttribute('data-theme', currentTheme);
}

if (themeToggle) {
    themeToggle.addEventListener('click', toggleTheme);
}

// Form functionality
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    
    // Validación básica en frontend
    const name = form.name.value.trim();
    const surname = form.surname.value.trim();
    const email = form.email.value.trim();
    const ci = form.ci.value.trim();
    const password = form.password.value;
    const phone = form.phone.value.trim();
    
    // Validaciones
    if (!name || !surname || !email || !ci || !password || !phone) {
        alert('Todos los campos son obligatorios');
        return;
    }
    
    if (password.length < 6) {
        alert('La contraseña debe tener al menos 6 caracteres');
        return;
    }
    
    if (!email.includes('@')) {
        alert('Por favor ingrese un email válido');
        return;
    }
    
    const data = {
        name: name,
        surname: surname,
        email: email,
        ci: ci,
        password: password,
        phone: phone
    };

    console.log('Enviando datos:', JSON.stringify(data));

    fetch('../APIS/API_Usuarios.php?action=register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Status HTTP:', response.status);
        console.log('Headers:', response.headers);
        
        // Obtener el texto de la respuesta para ver qué está devolviendo realmente
        return response.text();
    })
    .then(responseText => {
        console.log('Respuesta completa del servidor:', responseText);
        
        // Intentar parsear como JSON
        try {
            const data = JSON.parse(responseText);
            console.log('JSON parseado correctamente:', data);
            
            if (data.success) {
                alert('Usuario registrado correctamente');
                form.reset();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (jsonError) {
            console.error('Error al parsear JSON:', jsonError);
            console.error('Respuesta recibida no es JSON válido:', responseText);
            
            // Si la respuesta contiene HTML de error de PHP
            if (responseText.includes('<?php') || responseText.includes('<br />') || responseText.includes('Fatal error')) {
                alert('Error en el servidor PHP. Revisa la consola para más detalles.');
            } else {
                alert('Error de comunicación con el servidor');
            }
        }
    })
    .catch(error => {
        console.error('Error de red:', error);
        alert('Error de conexión: ' + error.message);
    });
});