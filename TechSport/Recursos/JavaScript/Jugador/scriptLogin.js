document.getElementById("loginForm").addEventListener("submit", function (event) {
    event.preventDefault();

    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    const formData = new FormData();
    formData.append("email", email);
    formData.append("password", password);

    fetch("/TechSport/Recursos/PHP/Jugador/login.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = "/TechSport/Páginas/Privadas/Jugador/inicio.html";
        } else {
            document.getElementById("alertMessage").style.display = "block";
        }
    })
    .catch(error => {
        console.error("Error en la solicitud:", error);
    });
});