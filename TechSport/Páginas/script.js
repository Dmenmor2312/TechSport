document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const menuBtn = document.getElementById('menuBtn');
    const navMenu = document.getElementById('navMenu');
    const logo = document.querySelector('.logo-circular');
    const overlay = document.querySelector('.overlay');
    const navLinks = document.querySelectorAll('.menu-principal a');
    const scrollLinks = document.querySelectorAll('a[href^="#"]');
  
    // Estado del menú
    let isMenuOpen = false;
  
    // Función para abrir/cerrar el menú
    function toggleMenu() {
      isMenuOpen = !isMenuOpen;
      navMenu.classList.toggle('mostrar');
      overlay.classList.toggle('mostrar');
      menuBtn.setAttribute('aria-expanded', isMenuOpen);
      
      // Bloquear/desbloquear scroll del body
      document.body.style.overflow = isMenuOpen ? 'hidden' : 'auto';
    }
  
    // Función para cerrar el menú
    function closeMenu() {
      if (isMenuOpen) {
        isMenuOpen = false;
        navMenu.classList.remove('mostrar');
        overlay.classList.remove('mostrar');
        menuBtn.setAttribute('aria-expanded', false);
        document.body.style.overflow = 'auto';
      }
    }
  
    // Función para el efecto del logo
    function toggleLogo() {
      this.classList.toggle('logo-ampliado');
      overlay.classList.toggle('mostrar');
      document.body.style.overflow = this.classList.contains('logo-ampliado') ? 'hidden' : 'auto';
    }
  
    // Smooth scrolling
    function handleScroll(e) {
      const targetId = this.getAttribute('href');
      if (targetId !== '#') {
        e.preventDefault();
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
          // Cerrar menú si está abierto
          closeMenu();
          
          // Scroll suave
          targetElement.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
          
          // Actualizar URL
          history.pushState(null, null, targetId);
        }
      }
    }
  
    // Event Listeners
    menuBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      toggleMenu();
    });
  
    logo.addEventListener('click', function(e) {
      e.stopPropagation();
      toggleLogo.call(this);
    });
  
    overlay.addEventListener('click', function() {
      closeMenu();
      logo.classList.remove('logo-ampliado');
    });
  
    navLinks.forEach(link => {
      link.addEventListener('click', function() {
        closeMenu();
        
        // Si es un enlace de ancla, manejar el scroll
        const href = this.getAttribute('href');
        if (href.startsWith('#')) {
          setTimeout(() => {
            document.querySelector(href)?.scrollIntoView({
              behavior: 'smooth'
            });
          }, 300);
        }
      });
    });
  
    scrollLinks.forEach(link => {
      link.addEventListener('click', handleScroll);
    });
  
    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', function(e) {
      if (!navMenu.contains(e.target) && 
          !menuBtn.contains(e.target) &&
          isMenuOpen) {
        closeMenu();
      }
    });
  
    // Cerrar con tecla Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeMenu();
        logo.classList.remove('logo-ampliado');
        overlay.classList.remove('mostrar');
        document.body.style.overflow = 'auto';
      }
    });
  
    // Mejorar accesibilidad
    menuBtn.setAttribute('aria-label', 'Menú de navegación');
    menuBtn.setAttribute('aria-controls', 'navMenu');
    menuBtn.setAttribute('aria-expanded', 'false');
  
    // Marcar enlace activo
    function setActiveLink() {
      const scrollPosition = window.scrollY;
      
      navLinks.forEach(link => {
        const sectionId = link.getAttribute('href');
        if (sectionId.startsWith('#')) {
          const section = document.querySelector(sectionId);
          if (section) {
            const sectionTop = section.offsetTop - 100;
            const sectionBottom = sectionTop + section.offsetHeight;
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
              link.classList.add('active');
            } else {
              link.classList.remove('active');
            }
          }
        }
      });
    }
  
    window.addEventListener('scroll', setActiveLink);
    setActiveLink(); // Ejecutar al cargar
  });