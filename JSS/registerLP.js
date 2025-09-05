// Theme toggle functionality
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

themeToggle.addEventListener('click', toggleTheme);

// Form functionality
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const data = {
        name: form.name.value,
        surname: form.surname.value,
        email: form.email.value,
        ci: form.ci.value,
        password: form.password.value,
        phone: form.phone.value
    };

    console.log(JSON.stringify(data));

    fetch('API_Usuarios.php/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        alert('Usuario registrado correctamente');
        location.reload();
    })
    .catch(error => {
        alert('Error al registrar usuario');
        console.error(error);
    });
});