/* ===================================================== */
/*         EDITOR DE CALENDARIO ENTRENADOR/PROFESIONAL   */
/* ===================================================== */

const btnNuevo = document.getElementById("btn-nuevo-evento");
const modal = document.getElementById("modal-evento");
const cerrarModal = document.getElementById("cerrar-modal");
const guardarEvento = document.getElementById("guardar-evento");

const nombreInput = document.getElementById("evento-nombre");
const fechaInput = document.getElementById("evento-fecha");
const descripcionInput = document.getElementById("evento-descripcion");

let modoEdicion = false;
let eventoActual = null;

/* Abrir modal */
btnNuevo.addEventListener("click", () => {
    modoEdicion = false;
    eventoActual = null;

    nombreInput.value = "";
    fechaInput.value = "";
    descripcionInput.value = "";

    modal.classList.remove("hidden");
});

/* Cerrar modal */
cerrarModal.addEventListener("click", () => {
    modal.classList.add("hidden");
});

/* Guardar evento */
guardarEvento.addEventListener("click", () => {

    if (!nombreInput.value || !fechaInput.value) {
        alert("Rellena el nombre y la fecha del evento.");
        return;
    }

    const nuevoEvento = {
        title: nombreInput.value,
        date: fechaInput.value,
        description: descripcionInput.value,
    };

    agregarEventoCalendario(nuevoEvento);

    agregarNotificacion("Nuevo evento añadido al calendario.", "evento");

    modal.classList.add("hidden");
});

/* ============================ */
/* CREAR EVENTO EN CALENDARIO  */
/* ============================ */

function agregarEventoCalendario(evento) {
    if (window.calendar) {
        calendar.addEvent(evento);
    }
}