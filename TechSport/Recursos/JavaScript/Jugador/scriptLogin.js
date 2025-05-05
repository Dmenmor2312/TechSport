document.getElementById("loginForm").addEventListener("submit", function(event) {
    event.preventDefault();
    var loginSuccess = false;
    if (!loginSuccess) {
        document.getElementById("alertMessage").style.display = "block";
    } else {
        window.location.href = "/TechSport/Páginas/Privadas/Jugador/inicio.html";
    }
});
