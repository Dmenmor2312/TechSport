/* ============================= */
/* PERMISOS POR ROL */
/* ============================= */
document.addEventListener("DOMContentLoaded", () => {
    const rol = document.body.dataset.rol;

    const puedeEditar = (rol === "entrenador" || rol === "profesional");
    const btnNuevo = document.getElementById("btn-nuevo-evento");

    if (puedeEditar) btnNuevo.style.display = "block";

    generarCalendario(puedeEditar);

    manejarScrollEventos();
});

/* ============================= */
/* EVENTOS (VACÍO) */
/* ============================= */
const eventos = []; // ← VACÍO COMO PEDISTE

/* ============================= */
/* CALENDARIO */
/* ============================= */
function obtenerDiasDelMes(year, month) {
    const fecha = new Date(year, month, 0);
    const numDias = fecha.getDate();

    let primerDia = new Date(year, month - 1, 1).getDay();
    primerDia = (primerDia === 0) ? 6 : primerDia - 1;

    return { numDias, primerDia };
}

function generarCalendario(puedeEditar) {
    const calendario = document.getElementById('calendar');
    calendario.innerHTML = '';

    const diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    diasSemana.forEach(dia => {
        const header = document.createElement('div');
        header.classList.add('calendar-header');
        header.textContent = dia;
        calendario.appendChild(header);
    });

    const fechaActual = new Date();
    const { numDias, primerDia } = obtenerDiasDelMes(fechaActual.getFullYear(), fechaActual.getMonth() + 1);

    for (let i = 0; i < primerDia; i++) {
        calendario.appendChild(document.createElement('div'));
    }

    for (let i = 1; i <= numDias; i++) {
        const celda = document.createElement('div');
        celda.classList.add('calendar-day');
        celda.textContent = i;

        if (puedeEditar) {
            celda.addEventListener("click", () => {
                alert(`Aquí abrirías un modal para agregar/editar evento del día ${i}`);
            });
        }

        calendario.appendChild(celda);
    }
}

/* ============================= */
/* SCROLL PARA MOSTRAR TARJETAS */
/* ============================= */
function manejarScrollEventos() {
    const eventBox = document.getElementById('eventos');
    if (!eventBox) return;

    window.addEventListener('scroll', () => {
        eventBox.classList.toggle('visible', window.scrollY > window.innerHeight * 0.5);
    });
}
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

/* ===================================== */
/*     NOTIFICACIONES DEFINIDAS POR ROL  */
/* ===================================== */

const notificacionesPorRol = {
    jugador: [
        { tipo: "mensaje", texto: "Mensaje del entrenador: recuerda llegar 10 min antes al entrenamiento." },
        { tipo: "mensaje", texto: "Mensaje del fisioterapeuta: revisión programada para esta semana." },
        { tipo: "evento", texto: "Se ha añadido un nuevo entrenamiento al calendario." },
        { tipo: "sistema", texto: "Tu planificación semanal ha sido actualizada." }
    ],
    entrenador: [
        { tipo: "mensaje", texto: "Tienes un mensaje de un profesional del equipo." },
        { tipo: "evento", texto: "Un jugador ha confirmado asistencia." },
        { tipo: "sistema", texto: "El calendario compartido se ha sincronizado." }
    ],
    profesional: [
        { tipo: "mensaje", texto: "Nuevo mensaje del entrenador sobre un jugador." },
        { tipo: "evento", texto: "Se ha asignado una nueva sesión a un jugador." },
        { tipo: "sistema", texto: "Se ha actualizado la carga de trabajo del equipo." }
    ]
};

/* ===================================== */
/*   Generar elemento con icono y color  */
/* ===================================== */

function crearItemNotificacion(tipo, texto) {
    const li = document.createElement("li");
    li.classList.add("noti-item", `noti-${tipo}`);

    let icono = "⚙️";
    if (tipo === "evento") icono = "📅";
    if (tipo === "mensaje") icono = "💬";

    li.innerHTML = `
        <span class="noti-icon">${icono}</span>
        <span>${texto}</span>
    `;

    return li;
}

/* ===================================== */
/*       Cargar notificaciones por rol   */
/* ===================================== */

function cargarNotificacionesPorRol() {
    const rol = document.body.dataset.rol;  
    const lista = document.getElementById("lista-notificaciones");
    if (!lista) return;

    lista.innerHTML = "";

    const notis = notificacionesPorRol[rol] || [];
    notis.forEach(noti => {
        lista.appendChild(crearItemNotificacion(noti.tipo, noti.texto));
    });
}

/* ===================================== */
/*   Permite añadir nuevas notificaciones */
/* ===================================== */

function agregarNotificacion(texto, tipo = "sistema") {
    const lista = document.getElementById("lista-notificaciones");
    if (!lista) return;

    lista.prepend(crearItemNotificacion(tipo, texto));
}

/* ===================================== */
/*   Animación de aparición con scroll   */
/* ===================================== */

function manejarScrollNotificaciones() {
    const notiCard = document.getElementById('notificaciones');
    if (!notiCard) return;

    window.addEventListener('scroll', () => {
        notiCard.classList.toggle('visible', window.scrollY > window.innerHeight * 0.55);
    });
}

/* ===================================== */
/*      Inicialización final del módulo  */
/* ===================================== */

document.addEventListener("DOMContentLoaded", () => {
    cargarNotificacionesPorRol();
    manejarScrollNotificaciones();
});