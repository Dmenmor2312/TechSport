document.addEventListener('DOMContentLoaded', function () {
    // Variables globales
    let fechaActual = new Date();
    let eventos = JSON.parse(localStorage.getItem('eventosProfesional')) || [];

    // Elementos del DOM - con verificaciones de existencia
    const calendarEl = document.getElementById('calendar');
    const btnNuevoEvento = document.getElementById('btn-nuevo-evento');
    const eventosContainer = document.getElementById('eventos');
    const listaNotificaciones = document.getElementById('lista-notificaciones');
    const menuBtn = document.getElementById('menuBtn');
    const navMenu = document.getElementById('navMenu');

    // Verificar si estamos en la pantalla de inicio
    const esPantallaInicio = window.location.pathname.includes('index.html') || 
                            window.location.pathname === '/' || 
                            window.location.pathname === '' ||
                            document.body.classList.contains('inicio') ||
                            !window.location.pathname.includes('admin');

    // Inicializar el calendario solo si existe el elemento
    if (calendarEl) {
        inicializarCalendario();
    }

    // Configurar eventos - solo si NO estamos en pantalla de inicio y el botón existe
    if (!esPantallaInicio && btnNuevoEvento) {
        btnNuevoEvento.addEventListener('click', mostrarFormularioNuevoEvento);
    } else if (btnNuevoEvento) {
        // Ocultar botón de nuevo evento en pantalla de inicio
        btnNuevoEvento.style.display = 'none';
    }

    // Menú hamburguesa - solo si los elementos existen
    if (menuBtn && navMenu) {
        menuBtn.addEventListener('click', function () {
            navMenu.classList.toggle('active');
        });
    }

    // Funciones principales
    function inicializarCalendario() {
        // Verificar que calendarEl existe antes de usarlo
        if (!calendarEl) return;

        // Generar estructura del calendario
        generarCalendario(fechaActual);

        // Mostrar eventos del mes actual solo si el contenedor existe
        if (eventosContainer) {
            mostrarEventosDelMes();
        }

        // Mostrar notificaciones solo si el contenedor existe
        if (listaNotificaciones) {
            mostrarNotificaciones();
        }
    }

    function generarCalendario(fecha) {
        // Verificar que calendarEl existe
        if (!calendarEl) return;

        const año = fecha.getFullYear();
        const mes = fecha.getMonth();

        // Crear contenedor del calendario
        calendarEl.innerHTML = '';

        // Resto del código de generarCalendario permanece igual...
        // Crear encabezado con navegación
        const encabezado = document.createElement('div');
        encabezado.className = 'calendario-encabezado';

        // Botón mes anterior
        const btnAnterior = document.createElement('button');
        btnAnterior.textContent = '◀';
        btnAnterior.className = 'btn-navegacion';
        btnAnterior.addEventListener('click', () => {
            fechaActual.setMonth(fechaActual.getMonth() - 1);
            generarCalendario(fechaActual);
            mostrarEventosDelMes();
        });

        // Título del mes y año
        const titulo = document.createElement('h2');
        titulo.textContent = `${obtenerNombreMes(mes)} ${año}`;
        titulo.className = 'calendario-titulo';

        // Botón mes siguiente
        const btnSiguiente = document.createElement('button');
        btnSiguiente.textContent = '▶';
        btnSiguiente.className = 'btn-navegacion';
        btnSiguiente.addEventListener('click', () => {
            fechaActual.setMonth(fechaActual.getMonth() + 1);
            generarCalendario(fechaActual);
            mostrarEventosDelMes();
        });

        encabezado.appendChild(btnAnterior);
        encabezado.appendChild(titulo);
        encabezado.appendChild(btnSiguiente);
        calendarEl.appendChild(encabezado);

        // Crear días de la semana
        const diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        const contenedorDiasSemana = document.createElement('div');
        contenedorDiasSemana.className = 'dias-semana';

        diasSemana.forEach(dia => {
            const diaElemento = document.createElement('div');
            diaElemento.textContent = dia;
            diaElemento.className = 'dia-semana';
            contenedorDiasSemana.appendChild(diaElemento);
        });

        calendarEl.appendChild(contenedorDiasSemana);

        // Crear cuadrícula de días
        const primerDia = new Date(año, mes, 1);
        const ultimoDia = new Date(año, mes + 1, 0);
        const primerDiaSemana = primerDia.getDay();
        const totalDias = ultimoDia.getDate();

        const contenedorDias = document.createElement('div');
        contenedorDias.className = 'dias-calendario';

        // Días vacíos al inicio
        let primerDiaAjustado = primerDiaSemana === 0 ? 6 : primerDiaSemana - 1;

        for (let i = 0; i < primerDiaAjustado; i++) {
            const diaVacio = document.createElement('div');
            diaVacio.className = 'dia-vacio';
            contenedorDias.appendChild(diaVacio);
        }

        // Días del mes
        for (let dia = 1; dia <= totalDias; dia++) {
            const diaElemento = document.createElement('div');
            diaElemento.className = 'dia-calendario';
            diaElemento.textContent = dia;

            // Verificar si es hoy
            const hoy = new Date();
            if (dia === hoy.getDate() && mes === hoy.getMonth() && año === hoy.getFullYear()) {
                diaElemento.classList.add('hoy');
            }

            // Verificar si hay eventos en este día
            const eventosDia = obtenerEventosDelDia(año, mes, dia);
            if (eventosDia.length > 0) {
                diaElemento.classList.add('con-eventos');

                // Añadir indicador de eventos
                const indicador = document.createElement('div');
                indicador.className = 'indicador-eventos';
                indicador.textContent = eventosDia.length;
                diaElemento.appendChild(indicador);

                // Mostrar eventos al hacer clic
                diaElemento.addEventListener('click', () => mostrarEventosDelDia(año, mes, dia));
            }

            // Permitir crear nuevo evento al hacer doble clic solo si NO estamos en pantalla de inicio
            if (!esPantallaInicio) {
                diaElemento.addEventListener('dblclick', () => mostrarFormularioNuevoEvento(año, mes, dia));
            } else {
                // En pantalla de inicio, mostrar solo información al hacer clic
                diaElemento.addEventListener('click', () => mostrarEventosDelDia(año, mes, dia));
            }

            contenedorDias.appendChild(diaElemento);
        }

        calendarEl.appendChild(contenedorDias);
    }

    function obtenerEventosDelDia(año, mes, dia) {
        return eventos.filter(evento => {
            const fechaEvento = new Date(evento.fecha);
            return fechaEvento.getFullYear() === año &&
                fechaEvento.getMonth() === mes &&
                fechaEvento.getDate() === dia;
        });
    }

    function mostrarEventosDelMes() {
        // Verificar que el contenedor existe
        if (!eventosContainer) return;

        const año = fechaActual.getFullYear();
        const mes = fechaActual.getMonth();

        const eventosMes = eventos.filter(evento => {
            const fechaEvento = new Date(evento.fecha);
            return fechaEvento.getFullYear() === año &&
                fechaEvento.getMonth() === mes;
        });

        eventosContainer.innerHTML = '<h2>Eventos del Mes</h2>';

        if (eventosMes.length === 0) {
            eventosContainer.innerHTML += '<p>No hay eventos programados para este mes.</p>';
            return;
        }

        // Ordenar eventos por fecha y hora
        eventosMes.sort((a, b) => {
            const fechaA = new Date(a.fecha + 'T' + a.horaInicio);
            const fechaB = new Date(b.fecha + 'T' + b.horaInicio);
            return fechaA - fechaB;
        });

        const listaEventos = document.createElement('ul');
        listaEventos.className = 'lista-eventos';

        eventosMes.forEach(evento => {
            const itemEvento = document.createElement('li');
            itemEvento.className = `evento-item tipo-${evento.tipo}`;

            const fecha = new Date(evento.fecha);
            const fechaFormateada = fecha.toLocaleDateString('es-ES', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });

            if (esPantallaInicio) {
                // En pantalla de inicio: solo mostrar información, sin botones de acción
                itemEvento.innerHTML = `
                    <div class="evento-info">
                        <span class="evento-fecha">${fechaFormateada}</span>
                        <span class="evento-titulo">${evento.titulo}</span>
                        <span class="evento-hora">${evento.horaInicio} - ${evento.horaFin}</span>
                        <span class="evento-tipo">${obtenerNombreTipo(evento.tipo)}</span>
                        ${evento.descripcion ? `<span class="evento-descripcion">${evento.descripcion}</span>` : ''}
                    </div>
                `;
                
                // Hacer que el evento sea clickeable para ver más detalles (solo lectura)
                itemEvento.addEventListener('click', () => mostrarDetallesEventoSoloLectura(evento));
            } else {
                // En otras pantallas: mostrar con botones de editar/eliminar
                itemEvento.innerHTML = `
                    <div class="evento-info">
                        <span class="evento-fecha">${fechaFormateada}</span>
                        <span class="evento-titulo">${evento.titulo}</span>
                        <span class="evento-hora">${evento.horaInicio} - ${evento.horaFin}</span>
                        <span class="evento-tipo">${obtenerNombreTipo(evento.tipo)}</span>
                    </div>
                    <div class="evento-acciones">
                        <button class="btn-editar" data-id="${evento.id}" title="Editar evento">✏️</button>
                        <button class="btn-eliminar" data-id="${evento.id}" title="Eliminar evento">🗑️</button>
                    </div>
                `;

                // Configurar eventos para los botones
                const btnEditar = itemEvento.querySelector('.btn-editar');
                const btnEliminar = itemEvento.querySelector('.btn-eliminar');

                if (btnEditar) {
                    btnEditar.addEventListener('click', (e) => {
                        e.stopPropagation();
                        editarEvento(evento.id);
                    });
                }
                
                if (btnEliminar) {
                    btnEliminar.addEventListener('click', (e) => {
                        e.stopPropagation();
                        eliminarEvento(evento.id);
                    });
                }

                // Hacer clickeable el evento para ver detalles
                itemEvento.addEventListener('click', () => mostrarDetallesCompletosEvento(evento));
            }

            listaEventos.appendChild(itemEvento);
        });

        eventosContainer.appendChild(listaEventos);
    }

    function mostrarEventosDelDia(año, mes, dia) {
        const eventosDia = obtenerEventosDelDia(año, mes, dia);

        if (eventosDia.length === 0) {
            alert(`No hay eventos programados para el ${dia}/${mes + 1}/${año}`);
            return;
        }

        const fecha = new Date(año, mes, dia);
        const fechaFormateada = fecha.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        let mensaje = `📅 Eventos para ${fechaFormateada}:\n\n`;

        // Ordenar eventos por hora
        eventosDia.sort((a, b) => a.horaInicio.localeCompare(b.horaInicio));

        eventosDia.forEach((evento, index) => {
            mensaje += `${index + 1}. 🕒 ${evento.horaInicio} - ${evento.horaFin}\n`;
            mensaje += `   📝 ${evento.titulo}\n`;
            mensaje += `   🏷️ ${obtenerNombreTipo(evento.tipo)}\n`;
            if (evento.descripcion) {
                mensaje += `   📋 ${evento.descripcion}\n`;
            }
            mensaje += '\n';
        });

        if (!esPantallaInicio) {
            // En modo administración: mostrar opciones de acción
            mensaje += `---\n`;
            mensaje += `¿Qué quieres hacer?\n`;
            mensaje += `1. Ver detalles de un evento específico\n`;
            mensaje += `2. Agregar nuevo evento\n`;
            mensaje += `3. Volver al calendario`;

            const opcion = prompt(mensaje + '\n\nEscribe el número de tu opción:');

            switch (opcion) {
                case '1':
                    const numEvento = prompt(`¿Qué evento quieres ver? (Escribe un número del 1 al ${eventosDia.length}):`);
                    const eventoIndex = parseInt(numEvento) - 1;
                    if (eventoIndex >= 0 && eventoIndex < eventosDia.length) {
                        mostrarDetallesCompletosEvento(eventosDia[eventoIndex]);
                    }
                    break;
                case '2':
                    mostrarFormularioNuevoEvento(año, mes, dia);
                    break;
                default:
                    // Simplemente volver al calendario
                    break;
            }
        } else {
            // En pantalla de inicio: solo mostrar información
            alert(mensaje);
        }
    }

    function mostrarDetallesCompletosEvento(evento) {
        const fecha = new Date(evento.fecha);
        const fechaFormateada = fecha.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        let mensaje = `📋 DETALLES COMPLETOS DEL EVENTO\n\n`;
        mensaje += `📝 Título: ${evento.titulo}\n`;
        mensaje += `📅 Fecha: ${fechaFormateada}\n`;
        mensaje += `🕒 Hora: ${evento.horaInicio} - ${evento.horaFin}\n`;
        mensaje += `🏷️ Tipo: ${obtenerNombreTipo(evento.tipo)}\n`;
        mensaje += `🆔 ID: ${evento.id}\n`;
        if (evento.descripcion) {
            mensaje += `📄 Descripción: ${evento.descripcion}\n`;
        }

        if (!esPantallaInicio) {
            const opcion = prompt(mensaje + `\n¿Qué quieres hacer?\n1. Editar este evento\n2. Eliminar este evento\n3. Volver`);

            switch (opcion) {
                case '1':
                    editarEvento(evento.id);
                    break;
                case '2':
                    eliminarEvento(evento.id);
                    break;
                default:
                    // Volver
                    break;
            }
        } else {
            // En pantalla de inicio: solo mostrar información
            alert(mensaje);
        }
    }

    function mostrarDetallesEventoSoloLectura(evento) {
        const fecha = new Date(evento.fecha);
        const fechaFormateada = fecha.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        let mensaje = `📋 DETALLES DEL EVENTO\n\n`;
        mensaje += `📝 Título: ${evento.titulo}\n`;
        mensaje += `📅 Fecha: ${fechaFormateada}\n`;
        mensaje += `🕒 Hora: ${evento.horaInicio} - ${evento.horaFin}\n`;
        mensaje += `🏷️ Tipo: ${obtenerNombreTipo(evento.tipo)}\n`;
        if (evento.descripcion) {
            mensaje += `📄 Descripción: ${evento.descripcion}\n`;
        }

        alert(mensaje);
    }

    function mostrarFormularioNuevoEvento(año, mes, dia) {
        // Si estamos en pantalla de inicio, no permitir crear eventos
        if (esPantallaInicio) {
            alert('No tienes permisos para crear eventos en la pantalla de inicio');
            return;
        }

        // CORREGIDO: Usar los parámetros correctamente para evitar el problema del día anterior
        const fechaSeleccionada = new Date(año, mes, dia);
        const fechaFormateada = fechaSeleccionada.toISOString().split('T')[0];

        // Crear formulario modal
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-contenido">
                <h2>➕ Nuevo Evento</h2>
                <form id="form-nuevo-evento">
                    <div class="form-group">
                        <label for="titulo">📝 Título:</label>
                        <input type="text" id="titulo" required placeholder="Ingresa el título del evento">
                    </div>
                    <div class="form-group">
                        <label for="fecha">📅 Fecha:</label>
                        <input type="date" id="fecha" value="${fechaFormateada}" required>
                    </div>
                    <div class="form-group">
                        <label for="hora-inicio">🕒 Hora inicio:</label>
                        <input type="time" id="hora-inicio" required>
                    </div>
                    <div class="form-group">
                        <label for="hora-fin">🕒 Hora fin:</label>
                        <input type="time" id="hora-fin" required>
                    </div>
                    <div class="form-group">
                        <label for="tipo">🏷️ Tipo de evento:</label>
                        <select id="tipo" required>
                            <option value="reunion">📊 Reunión</option>
                            <option value="entrenamiento">💪 Entrenamiento</option>
                            <option value="partido">⚽ Partido</option>
                            <option value="fisioterapia">🏥 Fisioterapia</option>
                            <option value="nutricion">🍎 Nutrición</option>
                            <option value="psicologia">🧠 Psicología</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="descripcion">📄 Descripción:</label>
                        <textarea id="descripcion" rows="4" placeholder="Descripción opcional del evento..."></textarea>
                    </div>
                    <div class="form-acciones">
                        <button type="button" id="btn-cancelar" class="btn-secundario">❌ Cancelar</button>
                        <button type="submit" class="btn-primary">💾 Guardar Evento</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        // Configurar eventos del formulario
        const form = document.getElementById('form-nuevo-evento');
        const btnCancelar = document.getElementById('btn-cancelar');

        if (form) {
            form.addEventListener('submit', guardarNuevoEvento);
        }
        
        if (btnCancelar) {
            btnCancelar.addEventListener('click', () => {
                if (document.body.contains(modal)) {
                    document.body.removeChild(modal);
                }
            });
        }

        // Cerrar modal al hacer clic fuera
        modal.addEventListener('click', (e) => {
            if (e.target === modal && document.body.contains(modal)) {
                document.body.removeChild(modal);
            }
        });

        // Poner foco en el primer campo
        setTimeout(() => {
            const tituloInput = document.getElementById('titulo');
            if (tituloInput) {
                tituloInput.focus();
            }
        }, 100);
    }

    function guardarNuevoEvento(e) {
        e.preventDefault();

        const titulo = document.getElementById('titulo');
        const fecha = document.getElementById('fecha');
        const horaInicio = document.getElementById('hora-inicio');
        const horaFin = document.getElementById('hora-fin');

        // Verificar que los elementos existen
        if (!titulo || !fecha || !horaInicio || !horaFin) {
            alert('❌ Error al guardar el evento');
            return;
        }

        // Validar que la hora fin sea mayor que la hora inicio
        if (horaInicio.value >= horaFin.value) {
            alert('❌ La hora de fin debe ser posterior a la hora de inicio');
            return;
        }

        const nuevoEvento = {
            id: 'evento_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
            titulo: titulo.value,
            fecha: fecha.value,
            horaInicio: horaInicio.value,
            horaFin: horaFin.value,
            tipo: document.getElementById('tipo').value,
            descripcion: document.getElementById('descripcion').value,
            fechaCreacion: new Date().toISOString()
        };

        eventos.push(nuevoEvento);
        guardarEventos();

        // Cerrar modal y actualizar vista
        const modal = document.querySelector('.modal');
        if (modal && document.body.contains(modal)) {
            document.body.removeChild(modal);
        }

        if (calendarEl) {
            generarCalendario(fechaActual);
        }
        
        if (eventosContainer) {
            mostrarEventosDelMes();
        }
        
        if (listaNotificaciones) {
            mostrarNotificaciones();
        }

        alert('✅ Evento guardado correctamente');
    }

    function editarEvento(id) {
        // Si estamos en pantalla de inicio, no permitir editar
        if (esPantallaInicio) {
            alert('No tienes permisos para editar eventos en la pantalla de inicio');
            return;
        }

        const evento = eventos.find(e => e.id === id);

        if (!evento) {
            alert('❌ Evento no encontrado');
            return;
        }

        // Crear formulario modal similar al de nuevo evento
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-contenido">
                <h2>✏️ Editar Evento</h2>
                <form id="form-editar-evento">
                    <div class="form-group">
                        <label for="titulo-editar">📝 Título:</label>
                        <input type="text" id="titulo-editar" value="${evento.titulo}" required>
                    </div>
                    <div class="form-group">
                        <label for="fecha-editar">📅 Fecha:</label>
                        <input type="date" id="fecha-editar" value="${evento.fecha}" required>
                    </div>
                    <div class="form-group">
                        <label for="hora-inicio-editar">🕒 Hora inicio:</label>
                        <input type="time" id="hora-inicio-editar" value="${evento.horaInicio}" required>
                    </div>
                    <div class="form-group">
                        <label for="hora-fin-editar">🕒 Hora fin:</label>
                        <input type="time" id="hora-fin-editar" value="${evento.horaFin}" required>
                    </div>
                    <div class="form-group">
                        <label for="tipo-editar">🏷️ Tipo de evento:</label>
                        <select id="tipo-editar" required>
                            <option value="reunion" ${evento.tipo === 'reunion' ? 'selected' : ''}>📊 Reunión</option>
                            <option value="entrenamiento" ${evento.tipo === 'entrenamiento' ? 'selected' : ''}>💪 Entrenamiento</option>
                            <option value="partido" ${evento.tipo === 'partido' ? 'selected' : ''}>⚽ Partido</option>
                            <option value="fisioterapia" ${evento.tipo === 'fisioterapia' ? 'selected' : ''}>🏥 Fisioterapia</option>
                            <option value="nutricion" ${evento.tipo === 'nutricion' ? 'selected' : ''}>🍎 Nutrición</option>
                            <option value="psicologia" ${evento.tipo === 'psicologia' ? 'selected' : ''}>🧠 Psicología</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="descripcion-editar">📄 Descripción:</label>
                        <textarea id="descripcion-editar" rows="4">${evento.descripcion || ''}</textarea>
                    </div>
                    <div class="form-info">
                        <small>🆔 ID: ${evento.id}</small>
                        <small>Creado: ${new Date(evento.fechaCreacion).toLocaleString()}</small>
                    </div>
                    <div class="form-acciones">
                        <button type="button" id="btn-cancelar-editar" class="btn-secundario">❌ Cancelar</button>
                        <button type="submit" class="btn-primary">💾 Actualizar Evento</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        // Configurar eventos del formulario
        const form = document.getElementById('form-editar-evento');
        const btnCancelar = document.getElementById('btn-cancelar-editar');

        if (form) {
            form.addEventListener('submit', (e) => actualizarEvento(e, id));
        }
        
        if (btnCancelar) {
            btnCancelar.addEventListener('click', () => {
                if (document.body.contains(modal)) {
                    document.body.removeChild(modal);
                }
            });
        }

        // Cerrar modal al hacer clic fuera
        modal.addEventListener('click', (e) => {
            if (e.target === modal && document.body.contains(modal)) {
                document.body.removeChild(modal);
            }
        });
    }

    function actualizarEvento(e, id) {
        e.preventDefault();

        const eventoIndex = eventos.findIndex(e => e.id === id);

        if (eventoIndex === -1) {
            alert('❌ Evento no encontrado');
            return;
        }

        const horaInicio = document.getElementById('hora-inicio-editar');
        const horaFin = document.getElementById('hora-fin-editar');

        // Verificar que los elementos existen
        if (!horaInicio || !horaFin) {
            alert('❌ Error al actualizar el evento');
            return;
        }

        // Validar que la hora fin sea mayor que la hora inicio
        if (horaInicio.value >= horaFin.value) {
            alert('❌ La hora de fin debe ser posterior a la hora de inicio');
            return;
        }

        eventos[eventoIndex] = {
            ...eventos[eventoIndex],
            titulo: document.getElementById('titulo-editar').value,
            fecha: document.getElementById('fecha-editar').value,
            horaInicio: horaInicio.value,
            horaFin: horaFin.value,
            tipo: document.getElementById('tipo-editar').value,
            descripcion: document.getElementById('descripcion-editar').value,
            fechaModificacion: new Date().toISOString()
        };

        guardarEventos();

        // Cerrar modal y actualizar vista
        const modal = document.querySelector('.modal');
        if (modal && document.body.contains(modal)) {
            document.body.removeChild(modal);
        }

        if (calendarEl) {
            generarCalendario(fechaActual);
        }
        
        if (eventosContainer) {
            mostrarEventosDelMes();
        }
        
        if (listaNotificaciones) {
            mostrarNotificaciones();
        }

        alert('✅ Evento actualizado correctamente');
    }

    function eliminarEvento(id) {
        // Si estamos en pantalla de inicio, no permitir eliminar
        if (esPantallaInicio) {
            alert('No tienes permisos para eliminar eventos en la pantalla de inicio');
            return;
        }

        const evento = eventos.find(e => e.id === id);
        
        if (!evento) {
            alert('❌ Evento no encontrado');
            return;
        }

        const confirmacion = confirm(`¿Estás seguro de que quieres eliminar el evento?\n\n"${evento.titulo}"\n${evento.fecha} - ${evento.horaInicio} a ${evento.horaFin}\n\nEsta acción no se puede deshacer.`);

        if (!confirmacion) {
            return;
        }

        eventos = eventos.filter(evento => evento.id !== id);
        guardarEventos();

        if (calendarEl) {
            generarCalendario(fechaActual);
        }
        
        if (eventosContainer) {
            mostrarEventosDelMes();
        }
        
        if (listaNotificaciones) {
            mostrarNotificaciones();
        }

        alert('✅ Evento eliminado correctamente');
    }

    function mostrarNotificaciones() {
        // Verificar que el contenedor existe
        if (!listaNotificaciones) return;

        const hoy = new Date();
        const eventosProximos = eventos.filter(evento => {
            const fechaEvento = new Date(evento.fecha);
            const diferencia = fechaEvento - hoy;
            const diasDiferencia = Math.ceil(diferencia / (1000 * 60 * 60 * 24));

            // Mostrar eventos de los próximos 7 días
            return diasDiferencia >= 0 && diasDiferencia <= 7;
        });

        listaNotificaciones.innerHTML = '';

        if (eventosProximos.length === 0) {
            listaNotificaciones.innerHTML = '<li class="no-notificaciones">📭 No hay eventos próximos</li>';
            return;
        }

        // Ordenar por fecha y hora
        eventosProximos.sort((a, b) => {
            const fechaA = new Date(a.fecha + 'T' + a.horaInicio);
            const fechaB = new Date(b.fecha + 'T' + b.horaInicio);
            return fechaA - fechaB;
        });

        eventosProximos.forEach(evento => {
            const fechaEvento = new Date(evento.fecha);
            const diferencia = fechaEvento - hoy;
            const diasDiferencia = Math.ceil(diferencia / (1000 * 60 * 60 * 24));

            let textoDias;
            if (diasDiferencia === 0) {
                textoDias = '🟡 Hoy';
            } else if (diasDiferencia === 1) {
                textoDias = '🟠 Mañana';
            } else if (diasDiferencia <= 3) {
                textoDias = `🔴 En ${diasDiferencia} días`;
            } else {
                textoDias = `⚫ En ${diasDiferencia} días`;
            }

            const itemNotificacion = document.createElement('li');
            itemNotificacion.className = 'notificacion-item';
            
            if (esPantallaInicio) {
                // En pantalla de inicio: solo mostrar información
                itemNotificacion.innerHTML = `
                    <div class="notificacion-contenido">
                        <strong>${evento.titulo}</strong>
                        <span class="notificacion-fecha">${textoDias} (${fechaEvento.toLocaleDateString('es-ES')})</span>
                        <span class="notificacion-hora">${evento.horaInicio} - ${evento.horaFin}</span>
                        <span class="notificacion-tipo">${obtenerNombreTipo(evento.tipo)}</span>
                    </div>
                `;
                
                // Hacer clickeable la notificación para ver detalles (solo lectura)
                itemNotificacion.addEventListener('click', () => mostrarDetallesEventoSoloLectura(evento));
            } else {
                // En otras pantallas: mostrar con botones de acción
                itemNotificacion.innerHTML = `
                    <div class="notificacion-contenido">
                        <strong>${evento.titulo}</strong>
                        <span class="notificacion-fecha">${textoDias} (${fechaEvento.toLocaleDateString('es-ES')})</span>
                        <span class="notificacion-hora">${evento.horaInicio} - ${evento.horaFin}</span>
                        <span class="notificacion-tipo">${obtenerNombreTipo(evento.tipo)}</span>
                    </div>
                    <div class="notificacion-acciones">
                        <button class="btn-notificacion-editar" data-id="${evento.id}" title="Editar">✏️</button>
                        <button class="btn-notificacion-eliminar" data-id="${evento.id}" title="Eliminar">🗑️</button>
                    </div>
                `;

                // Configurar eventos para los botones de notificación
                const btnEditar = itemNotificacion.querySelector('.btn-notificacion-editar');
                const btnEliminar = itemNotificacion.querySelector('.btn-notificacion-eliminar');

                if (btnEditar) {
                    btnEditar.addEventListener('click', (e) => {
                        e.stopPropagation();
                        editarEvento(evento.id);
                    });
                }
                
                if (btnEliminar) {
                    btnEliminar.addEventListener('click', (e) => {
                        e.stopPropagation();
                        eliminarEvento(evento.id);
                    });
                }

                // Hacer clickeable la notificación para ver detalles
                itemNotificacion.addEventListener('click', () => mostrarDetallesCompletosEvento(evento));
            }

            listaNotificaciones.appendChild(itemNotificacion);
        });
    }

    function guardarEventos() {
        localStorage.setItem('eventosProfesional', JSON.stringify(eventos));
    }

    function obtenerNombreTipo(tipo) {
        const tipos = {
            'reunion': '📊 Reunión',
            'entrenamiento': '💪 Entrenamiento',
            'partido': '⚽ Partido',
            'fisioterapia': '🏥 Fisioterapia',
            'nutricion': '🍎 Nutrición',
            'psicologia': '🧠 Psicología'
        };
        return tipos[tipo] || tipo;
    }

    function obtenerNombreMes(mes) {
        const meses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        return meses[mes];
    }
});