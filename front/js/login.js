"use strict";

const formulario = document.getElementById("formulario-login");
const identificador = document.getElementById("identificador");
const contrasena = document.getElementById("contrasena");
const mensaje = document.getElementById("mensaje");
const botonContrasena = document.getElementById("alternar-contrasena");

botonContrasena.addEventListener("click", function () {
    const mostrar = contrasena.type === "password";
    contrasena.type = mostrar ? "text" : "password";
    botonContrasena.textContent = mostrar ? "Ocultar contraseña" : "Mostrar contraseña";
    botonContrasena.setAttribute("aria-pressed", String(mostrar));
});

formulario.addEventListener("submit", function (evento) {
    evento.preventDefault();

    const faltaIdentificador = identificador.value.trim() === "";
    const faltaContrasena = contrasena.value.trim() === "";

    identificador.setAttribute("aria-invalid", String(faltaIdentificador));
    contrasena.setAttribute("aria-invalid", String(faltaContrasena));

    if (faltaIdentificador || faltaContrasena) {
        mensaje.className = "mensaje error";
        mensaje.textContent = "Debes ingresar tu usuario o correo y tu contraseña.";
        (faltaIdentificador ? identificador : contrasena).focus();
        return;
    }

    mensaje.className = "mensaje informacion";
    mensaje.textContent = "Campos completos. La conexión con el servicio de autenticación está pendiente.";
    // Día 2: consumir el endpoint de login con fetch y procesar su respuesta.
    // No se almacenan contraseñas ni se simula una sesión.
});

