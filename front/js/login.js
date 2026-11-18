"use strict";

const formulario = document.getElementById("formulario-login");
const identificador = document.getElementById("identificador");
const contrasena = document.getElementById("contrasena");
const mensaje = document.getElementById("mensaje");

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
   
});

