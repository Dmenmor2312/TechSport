// Gestión del calendario y eventos
class GestorCalendario {
    constructor() {
        this.fechaActual = new Date();
        this.eventos = JSON.parse(localStorage.getItem('eventosProfesional')) || [];
        this.inicializarCalendario();
        this.inicializarEventos();
    }

    inicializarCalendario() {
        this.actualizarCalendario();

        // Navegación entre meses
        const btnMesAnterior = document.getElementById('btn-mes-anterior');
        const btnMesSiguiente = document.getElementById('btn-mes-siguiente');
        
        if (btnMesAnterior) {
            btnMesAnterior.addEventListener('click', () => {
                this.fechaActual.setMonth(this.fechaActual.getMonth() - 1);
                this.actualizarCalendario();
            });
        }

        if (btnMesSiguiente) {
            btnMesSiguiente.addEventListener('click', () => {
                this.fechaActual.setMonth(this.fechaActual.getMonth() + 1);
                this.actualizarCalendario();
            });
        }

        // Botón editar calendario
        const btnEditarCalendario = document.getElementById('btn-editar-calendario');
        if (btnEditarCalendario) {
            btnEditarCalendario.addEventListener('click', () => {
                window.location.href = '/TechSport/Páginas/Privadas/Profesional/calendario-editor.html';
            });
        }
    }

    inicializarEventos() {
        // Escuchar cambios en localStorage
        window.addEventListener('storage', (e) => {
            if (e.key === 'eventosProfesional') {
                this.eventos = JSON.parse(e.newValue) || [];
                this.actualizarCalendario();
                this.actualizarListaEventos();
            }
        });

        // También escuchar cambios en la misma pestaña
        window.addEventListener('localStorageUpdated', () => {
            this.eventos = JSON.parse(localStorage.getItem('eventosProfesional')) || [];
            this.actualizarCalendario();
            this.actualizarListaEventos();
        });
    }

    actualizarCalendario() {
        const titulo = document.getElementById('calendario-titulo');
        const diasContainer = document.getElementById('dias-calendario');

        if (!titulo || !diasContainer) {
            console.error('Elementos del calendario no encontrados');
            return;
        }

        // Formatear título del mes y año
        const opciones = { month: 'long', year: 'numeric' };
        titulo.textContent = this.fechaActual.toLocaleDateString('es-ES', opciones);

        // Limpiar calendario
        diasContainer.innerHTML = '';

        // Obtener primer día del mes y último día
        const primerDia = new Date(this.fechaActual.getFullYear(), this.fechaActual.getMonth(), 1);
        const ultimoDia = new Date(this.fechaActual.getFullYear(), this.fechaActual.getMonth() + 1, 0);

        // CORRECCIÓN: Calcular correctamente el primer día de la semana (Lunes = 0)
        let primerDiaSemana = primerDia.getDay();
        // Convertir: Domingo=0, Lunes=1, ..., Sábado=6 → Lunes=0, Domingo=6
        primerDiaSemana = primerDiaSemana === 0 ? 6 : primerDiaSemana - 1;

        // Días vacíos al inicio
        for (let i = 0; i < primerDiaSemana; i++) {
            const diaVacio = document.createElement('div');
            diaVacio.className = 'dia-vacio';
            diasContainer.appendChild(diaVacio);
        }

        // Días del mes
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        for (let dia = 1; dia <= ultimoDia.getDate(); dia++) {
            const fecha = new Date(this.fechaActual.getFullYear(), this.fechaActual.getMonth(), dia);
            const diaElement = document.createElement('div');
            diaElement.className = 'dia-calendario';
            diaElement.textContent = dia;
            diaElement.setAttribute('data-fecha', fecha.toISOString().split('T')[0]);

            // Marcar día actual
            if (fecha.toDateString() === hoy.toDateString()) {
                diaElement.classList.add('hoy');
            }

            // Verificar eventos para este día
            const eventosDia = this.obtenerEventosDelDia(fecha);
            
            if (eventosDia.length > 0) {
                // Agregar indicador de eventos
                const indicador = document.createElement('div');
                indicador.className = 'indicador-eventos';
                indicador.textContent = eventosDia.length;
                diaElement.appendChild(indicador);

                // Agregar tipos de eventos como data attribute para los estilos CSS
                const tiposEventos = eventosDia.map(evento => evento.tipo).join(' ');
                diaElement.setAttribute('data-event-types', tiposEventos);

                // Aplicar clase según el primer tipo de evento
                if (eventosDia.length > 0) {
                    diaElement.classList.add(`tipo-${eventosDia[0].tipo}`);
                }
            }

            // Mostrar eventos al hacer clic
            diaElement.addEventListener('click', () => {
                this.mostrarEventosDelDia(fecha, eventosDia);
            });

            diasContainer.appendChild(diaElement);
        }

        this.actualizarListaEventos();
    }

    obtenerEventosDelDia(fecha) {
        const fechaStr = fecha.toISOString().split('T')[0];
        return this.eventos.filter(evento => {
            const eventoFecha = new Date(evento.fecha);
            const eventoFechaStr = eventoFecha.toISOString().split('T')[0];
            return eventoFechaStr === fechaStr;
        });
    }

    mostrarEventosDelDia(fecha, eventos) {
        const fechaFormateada = fecha.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        let mensaje = `Eventos para ${fechaFormateada}:\n\n`;

        if (eventos.length === 0) {
            mensaje += 'No hay eventos programados para este día.';
        } else {
            eventos.forEach((evento, index) => {
                mensaje += `${index + 1}. ${evento.titulo} (${evento.tipo})\n`;
                mensaje += `   Hora: ${evento.hora}\n`;
                if (evento.descripcion) {
                    mensaje += `   Descripción: ${evento.descripcion}\n`;
                }
                mensaje += '\n';
            });
        }

        // Usar un modal más elegante en lugar de alert
        this.mostrarModalEventos(fechaFormateada, eventos);
    }

    mostrarModalEventos(titulo, eventos) {
        // Crear modal dinámico
        const modal = document.createElement('div');
        modal.className = 'modal-evento';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        `;

        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        `;

        let contenidoHTML = `<h3 style="margin-bottom: 15px; color: #143b8d;">${titulo}</h3>`;
        
        if (eventos.length === 0) {
            contenidoHTML += '<p>No hay eventos programados para este día.</p>';
        } else {
            eventos.forEach((evento, index) => {
                contenidoHTML += `
                    <div class="evento-detalle" style="
                        background: #f8f9fa;
                        padding: 15px;
                        margin-bottom: 10px;
                        border-radius: 6px;
                        border-left: 4px solid ${this.obtenerColorEvento(evento.tipo)};
                    ">
                        <h4 style="margin: 0 0 5px 0; color: #2d3748;">${evento.titulo}</h4>
                        <p style="margin: 0 0 5px 0; color: #4a5568;">
                            <strong>Tipo:</strong> ${evento.tipo}<br>
                            <strong>Hora:</strong> ${evento.hora}
                        </p>
                        ${evento.descripcion ? `<p style="margin: 0; color: #718096;">${evento.descripcion}</p>` : ''}
                    </div>
                `;
            });
        }

        modalContent.innerHTML = contenidoHTML;

        // Botón cerrar
        const btnCerrar = document.createElement('button');
        btnCerrar.textContent = 'Cerrar';
        btnCerrar.style.cssText = `
            background: #143b8d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 15px;
            width: 100%;
        `;
        btnCerrar.addEventListener('click', () => {
            document.body.removeChild(modal);
        });

        modalContent.appendChild(btnCerrar);
        modal.appendChild(modalContent);
        document.body.appendChild(modal);

        // Cerrar modal al hacer clic fuera
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }

    obtenerColorEvento(tipo) {
        const colores = {
            'reunion': '#2b6cb0',
            'entrenamiento': '#276749',
            'partido': '#c53030',
            'fisioterapia': '#6b46c1',
            'nutricion': '#d69e2e',
            'psicologia': '#dd6b20'
        };
        return colores[tipo] || '#718096';
    }

    actualizarListaEventos() {
        const listaEventos = document.getElementById('lista-eventos');
        if (!listaEventos) return;

        const eventosMes = this.obtenerEventosDelMes();

        if (eventosMes.length === 0) {
            listaEventos.innerHTML = '<p>No hay eventos programados para este mes.</p>';
            return;
        }

        listaEventos.innerHTML = eventosMes.map(evento => `
            <div class="evento-item tipo-${evento.tipo}">
                <div class="evento-info">
                    <div class="evento-fecha">${new Date(evento.fecha).toLocaleDateString('es-ES')}</div>
                    <div class="evento-titulo">${evento.titulo}</div>
                    <div class="evento-hora">${evento.hora}</div>
                    ${evento.descripcion ? `<div class="evento-descripcion">${evento.descripcion}</div>` : ''}
                </div>
            </div>
        `).join('');
    }

    obtenerEventosDelMes() {
        const primerDiaMes = new Date(this.fechaActual.getFullYear(), this.fechaActual.getMonth(), 1);
        const ultimoDiaMes = new Date(this.fechaActual.getFullYear(), this.fechaActual.getMonth() + 1, 0);

        return this.eventos.filter(evento => {
            const fechaEvento = new Date(evento.fecha);
            return fechaEvento >= primerDiaMes && fechaEvento <= ultimoDiaMes;
        }).sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
    }

    // Método para actualizar eventos desde el editor
    actualizarEventos(nuevosEventos) {
        this.eventos = nuevosEventos;
        localStorage.setItem('eventosProfesional', JSON.stringify(this.eventos));
        
        // Disparar evento personalizado para actualizar en la misma pestaña
        const event = new Event('localStorageUpdated');
        window.dispatchEvent(event);
        
        this.actualizarCalendario();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    // Verificar que estamos en la página correcta
    if (document.getElementById('calendario-titulo')) {
        window.gestorCalendario = new GestorCalendario();
    }

    // Cargar notificaciones
    cargarNotificaciones();
});

function cargarNotificaciones() {
    const listaNotificaciones = document.getElementById('lista-notificaciones');
    if (!listaNotificaciones) return;

    const notificaciones = JSON.parse(localStorage.getItem('notificacionesProfesional')) || [];

    if (notificaciones.length === 0) {
        listaNotificaciones.innerHTML = '<li class="notificacion-item">No hay notificaciones nuevas</li>';
        return;
    }

    listaNotificaciones.innerHTML = notificaciones.map(notif => `
        <li class="notificacion-item">
            <strong>${notif.titulo}</strong>
            <span>${notif.mensaje}</span>
            <small>${new Date(notif.fecha).toLocaleDateString('es-ES')}</small>
        </li>
    `).join('');
}

// Función auxiliar para debug
function debugCalendario() {
    console.log('Eventos en localStorage:', JSON.parse(localStorage.getItem('eventosProfesional') || '[]'));
    console.log('Fecha actual:', new Date());
}