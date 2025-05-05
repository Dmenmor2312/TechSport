document.getElementById("loginForm").addEventListener("submit", function(event) {
    event.preventDefault();

    var loginSuccess = false;

    if (!loginSuccess) {
        // Mostrar la alerta de "No tienes cuenta"
        document.getElementById("alertMessage").style.display = "block";
    } else {
        // Redirigir al dashboard o página principal si el login es correcto
        window.location.href = "/TechSport/Páginas/Privadas/Entrenador/inicio.html";
    }
});
