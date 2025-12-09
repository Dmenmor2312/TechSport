function limpiarErrores() {
    // Limpiar mensajes de error
    document.querySelectorAll('.input-error').forEach(el => {
        el.textContent = '';
    });
    
    // Limpiar clases de error
    document.querySelectorAll('input').forEach(input => {
        input.classList.remove('error');
    });
    
    // Ocultar mensaje general de error
    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) errorMessage.style.display = 'none';
}

function mostrarErrorCampo(campoId, mensaje) {
    const campo = document.getElementById(campoId);
    const errorElement = document.getElementById(`${campoId}Error`);
    
    if (campo && errorElement) {
        campo.classList.add('error');
        errorElement.textContent = mensaje;
    }
}

function validarEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validarFormulario(email, password) {
    let valido = true;
    
    if (!email) {
        mostrarErrorCampo('email', 'El correo electrónico es requerido');
        valido = false;
    } else if (!validarEmail(email)) {
        mostrarErrorCampo('email', 'Ingrese un correo electrónico válido');
        valido = false;
    }

    if (!password) {
        mostrarErrorCampo('password', 'La contraseña es requerida');
        valido = false;
    } else if (password.length < 8) {
        mostrarErrorCampo('password', 'La contraseña debe tener al menos 8 caracteres');
        valido = false;
    }

    return valido;
}