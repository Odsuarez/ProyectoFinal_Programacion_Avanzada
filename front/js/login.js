"use strict";

const formulario = document.getElementById("formulario-login");
const identificador = document.getElementById("identificador");
const contrasena = document.getElementById("contrasena");
const mensaje = document.getElementById("mensaje");
const botonContrasena = document.getElementById("alternar-contrasena");
const botonIngreso = formulario.querySelector('button[type="submit"]');

[identificador, contrasena].forEach(function (campo) {
    campo.addEventListener("input", function () {
        mensaje.textContent = "";
        mensaje.className = "mensaje";
        if (campo.value.trim() !== "") campo.setAttribute("aria-invalid", "false");
    });
});

botonContrasena.addEventListener("click", function () {
    const mostrar = contrasena.type === "password";
    contrasena.type = mostrar ? "text" : "password";
    botonContrasena.textContent = mostrar ? "Ocultar contraseña" : "Mostrar contraseña";
    botonContrasena.setAttribute("aria-pressed", String(mostrar));
});

formulario.addEventListener("submit", async function (evento) {
    evento.preventDefault();
    if (botonIngreso.disabled) return;

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

    botonIngreso.disabled = true;
    botonIngreso.textContent = "Ingresando...";
    mensaje.textContent = "";
    try {
        const respuesta = await fetch("/login", {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify({
                identificador: identificador.value.trim(),
                contrasena: contrasena.value
            })
        });
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.mensaje || "No se pudo iniciar sesión.");
        sessionStorage.setItem("token", datos.token);
        contrasena.value = "";
        window.location.assign("/inicio");
    } catch (error) {
        mensaje.className = "mensaje error";
        mensaje.textContent = error instanceof TypeError
            ? "No se pudo conectar con el servicio. Intenta nuevamente."
            : error.message;
    } finally {
        botonIngreso.disabled = false;
        botonIngreso.textContent = "Iniciar sesión";
    }
});

