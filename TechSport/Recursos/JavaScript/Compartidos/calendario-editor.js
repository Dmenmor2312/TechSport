// Variables globales
// Función para obtener nombre del tipo de evento
function obtenerNombreTipo(tipo) {
    const nombres = {
        'reunion': 'Reunión',
        'entrenamiento': 'Entrenamiento',
        'partido': 'Partido',
        'gimnasio': 'Gimnasio',
        'fisio': 'Fisioterapia',
        'nutricionista': 'Nutrición',
        'psicologo': 'Psicología'
    };
    return nombres[tipo] || tipo;
}

// Función para obtener color por tipo
function obtenerColorPorTipo(tipo) {
    const colores = {
        'reunion': '#bee3f8',
        'entrenamiento': '#c6f6d5',
        'partido': '#fed7d7',
        'gimnasio': '#e9d8fd',
        'fisio': '#fefcbf',
        'nutricionista': '#fed7e2',
        'psicologo': '#c6f6d5'
    };
    return colores[tipo] || '#e2e8f0';
}

// Función para formatear fecha en español
function formatearFechaES(fecha) {
    const opciones = { month: 'long', year: 'numeric' };
    return fecha.toLocaleDateString('es-ES', opciones);
}

// Generar calendario
function generarCalendario(fecha) {
    const calendarGrid = document.getElementById('calendarGrid');
    const calendarTitle = document.getElementById('calendarTitle');

    // Actualizar título
    calendarTitle.textContent = formatearFechaES(fecha);

    // Obtener primer y último día del mes
    const primerDia = new Date(fecha.getFullYear(), fecha.getMonth(), 1);
    const ultimoDia = new Date(fecha.getFullYear(), fecha.getMonth() + 1, 0);

    // Obtener día de la semana del primer día (0=Domingo, 1=Lunes...)
    let primerDiaSemana = primerDia.getDay();
    primerDiaSemana = primerDiaSemana === 0 ? 6 : primerDiaSemana - 1;

    // Limpiar calendario (mantener encabezados)
    const diasActuales = calendarGrid.querySelectorAll('.day-cell');
    diasActuales.forEach(dia => dia.remove());

    // Añadir días vacíos al inicio
    for (let i = 0; i < primerDiaSemana; i++) {
        const diaVacio = document.createElement('div');
        diaVacio.className = 'day-cell';
        calendarGrid.appendChild(diaVacio);
    }

    // Añadir días del mes
    const totalDias = ultimoDia.getDate();
    const hoy = new Date();

    for (let dia = 1; dia <= totalDias; dia++) {
        const diaCell = document.createElement('div');
        diaCell.className = 'day-cell';

        // Verificar si es hoy
        const fechaCelda = new Date(fecha.getFullYear(), fecha.getMonth(), dia);
        if (fechaCelda.toDateString() === hoy.toDateString()) {
            diaCell.classList.add('today');
        }

        // Añadir número del día
        const dayNumber = document.createElement('div');
        dayNumber.className = 'day-number';
        dayNumber.textContent = dia;
        diaCell.appendChild(dayNumber);

        // Verificar si hay eventos en este día
        const eventosDia = eventos.filter(evento => {
            const fechaEvento = new Date(evento.fecha);
            return fechaEvento.getDate() === dia &&
                fechaEvento.getMonth() === fecha.getMonth() &&
                fechaEvento.getFullYear() === fecha.getFullYear();
        });

        if (eventosDia.length > 0) {
            diaCell.classList.add('has-events');

            const eventosContainer = document.createElement('div');
            eventosContainer.className = 'day-events';

            // Mostrar mini eventos
            eventosDia.forEach(evento => {
                const miniEvent = document.createElement('div');
                miniEvent.className = 'mini-event';
                miniEvent.style.backgroundColor = evento.color;
                const hora = new Date(evento.fecha).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                miniEvent.title = `${evento.titulo} (${hora})`;
                miniEvent.textContent = evento.titulo.substring(0, 15) + (evento.titulo.length > 15 ? '...' : '');
                eventosContainer.appendChild(miniEvent);
            });

            diaCell.appendChild(eventosContainer);

            // Hacer clic para mostrar opciones
            diaCell.addEventListener('click', function (e) {
                if (e.target.classList.contains('mini-event')) return;
                mostrarOpcionesDia(dia, fecha.getMonth(), fecha.getFullYear(), eventosDia);
            });
        } else {
            // Permitir crear evento en día vacío
            diaCell.addEventListener('click', function () {
                mostrarFormularioParaDia(dia, fecha.getMonth(), fecha.getFullYear());
            });
        }

        calendarGrid.appendChild(diaCell);
    }
}

// Mostrar opciones para un día con eventos
function mostrarOpcionesDia(dia, mes, año, eventosDia) {
    const fecha = new Date(año, mes, dia);
    const fechaFormateada = fecha.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    let mensaje = `📅 ${fechaFormateada}\n\n`;

    if (eventosDia.length === 0) {
        mensaje += 'No hay eventos programados para este día.\n\n';
        mensaje += '¿Quieres crear un nuevo evento?';

        if (confirm(mensaje)) {
            mostrarFormularioParaDia(dia, mes, año);
        }
    } else {
        mensaje += `Eventos programados (${eventosDia.length}):\n\n`;

        eventosDia.forEach((evento, index) => {
            const hora = new Date(evento.fecha).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
            mensaje += `${index + 1}. ${hora}\n`;
            mensaje += `   ${evento.titulo}\n`;
            mensaje += `   Tipo: ${obtenerNombreTipo(evento.tipo)}\n\n`;
        });

        mensaje += '¿Qué quieres hacer?\n';
        mensaje += '1. Ver detalles de un evento\n';
        mensaje += '2. Crear nuevo evento\n';
        mensaje += '3. Cancelar';

        const opcion = prompt(mensaje);

        switch (opcion) {
            case '1':
                const numEvento = prompt(`¿Qué evento quieres ver? (1-${eventosDia.length}):`);
                const eventoIndex = parseInt(numEvento) - 1;
                if (eventoIndex >= 0 && eventoIndex < eventosDia.length) {
                    mostrarDetallesEvento(eventosDia[eventoIndex]);
                }
                break;
            case '2':
                mostrarFormularioParaDia(dia, mes, año);
                break;
        }
    }
}

// Mostrar formulario para crear evento en día específico
function mostrarFormularioParaDia(dia, mes, año) {
    const fecha = new Date(año, mes, dia);
    const fechaISO = fecha.toISOString().split('T')[0];

    document.getElementById('fecha').value = fechaISO;
    document.getElementById('titulo').focus();

    // Desplazar al formulario
    document.getElementById('titulo').scrollIntoView({ behavior: 'smooth' });
}

// Mostrar detalles del evento
function mostrarDetallesEvento(evento) {
    const fecha = new Date(evento.fecha);
    const fechaFormateada = fecha.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    const hora = fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

    let mensaje = `📋 DETALLES DEL EVENTO\n\n`;
    mensaje += `📝 Título: ${evento.titulo}\n`;
    mensaje += `📅 Fecha: ${fechaFormateada}\n`;
    mensaje += `🕒 Hora: ${hora} (1h de duración)\n`;
    mensaje += `🏷️ Tipo: ${obtenerNombreTipo(evento.tipo)}\n`;
    mensaje += `🆔 ID: ${evento.id}\n`;

    if (evento.descripcion) {
        mensaje += `\n📄 Descripción:\n${evento.descripcion}\n`;
    }

    const opcion = prompt(mensaje + `\n¿Qué quieres hacer?\n1. Editar este evento\n2. Eliminar este evento\n3. Volver`);

    switch (opcion) {
        case '1':
            editarEvento(evento.id);
            break;
        case '2':
            eliminarEvento(evento.id, evento.titulo);
            break;
    }
}

// Editar evento
function editarEvento(eventoId) {
    // Buscar el evento
    const evento = eventos.find(e => e.id == eventoId);

    if (!evento) {
        alert('❌ Evento no encontrado');
        return;
    }

    // Llenar formulario modal
    document.getElementById('eventoId').value = evento.id;
    document.getElementById('edit_titulo').value = evento.titulo;

    // Extraer fecha y hora del DATETIME
    const fechaObj = new Date(evento.fecha);
    const fechaISO = fechaObj.toISOString().split('T')[0];
    const hora = fechaObj.toTimeString().substring(0, 5);

    document.getElementById('edit_fecha').value = fechaISO;
    document.getElementById('edit_hora_inicio').value = hora;
    document.getElementById('edit_tipo').value = evento.tipo;
    document.getElementById('edit_descripcion').value = evento.descripcion || '';

    // Mostrar modal
    document.getElementById('modalEditar').style.display = 'flex';
}

// Eliminar evento
function eliminarEvento(eventoId, titulo) {
    if (!confirm(`¿Estás seguro de que quieres eliminar el evento?\n\n"${titulo}"\n\nEsta acción no se puede deshacer.`)) {
        return;
    }

    // Crear formulario para eliminar
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';

    const inputAccion = document.createElement('input');
    inputAccion.type = 'hidden';
    inputAccion.name = 'accion';
    inputAccion.value = 'eliminar_evento';

    const inputEventoId = document.createElement('input');
    inputEventoId.type = 'hidden';
    inputEventoId.name = 'evento_id';
    inputEventoId.value = eventoId;

    form.appendChild(inputAccion);
    form.appendChild(inputEventoId);
    document.body.appendChild(form);
    form.submit();
}

// Cerrar modal
function cerrarModal() {
    document.getElementById('modalEditar').style.display = 'none';
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    generarCalendario(fechaActual);

    // Configurar navegación del calendario
    document.getElementById('prevMonth').addEventListener('click', () => {
        fechaActual.setMonth(fechaActual.getMonth() - 1);
        generarCalendario(fechaActual);
    });

    document.getElementById('nextMonth').addEventListener('click', () => {
        fechaActual.setMonth(fechaActual.getMonth() + 1);
        generarCalendario(fechaActual);
    });

    document.getElementById('today').addEventListener('click', () => {
        fechaActual = new Date();
        generarCalendario(fechaActual);
    });
});

// Cerrar modal al hacer clic fuera
window.onclick = function (event) {
    const modal = document.getElementById('modalEditar');
    if (event.target === modal) {
        cerrarModal();
    }
};