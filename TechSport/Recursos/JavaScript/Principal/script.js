// Control del menú hamburguesa
const menuBtn = document.getElementById('menuBtn');
const navMenu = document.getElementById('navMenu');

menuBtn.addEventListener('click', () => {
    menuBtn.classList.toggle('active');
    navMenu.classList.toggle('active');
    
    // Bloquear scroll cuando el menú está abierto
    if (navMenu.classList.contains('active')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

// Cerrar menú al hacer clic en un enlace
const navLinks = document.querySelectorAll('.nav-link');
navLinks.forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            menuBtn.classList.remove('active');
            navMenu.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

// Cerrar menú al redimensionar la pantalla
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        menuBtn.classList.remove('active');
        navMenu.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Interacción del logo
const logoInteractivo = document.getElementById('logo-interactivo');
if (logoInteractivo) {
    logoInteractivo.addEventListener('mouseenter', () => {
        logoInteractivo.style.transform = 'rotate(15deg)';
        setTimeout(() => {
            logoInteractivo.style.transform = 'rotate(-15deg)';
        }, 300);
        setTimeout(() => {
            logoInteractivo.style.transform = 'rotate(0)';
        }, 600);
    });
}