"use strict";

const estado = document.getElementById("estado-sesion");
const datosSesion = document.getElementById("datos-sesion");
const mensaje = document.getElementById("mensaje");
const cerrarSesion = document.getElementById("cerrar-sesion");
const volverLogin = document.getElementById("volver-login");

function volverAlLogin() {
    sessionStorage.removeItem("token");
    window.location.replace("/");
}

async function consultarSesion() {
    const token = sessionStorage.getItem("token");
    if (!token) {
        volverAlLogin();
        return;
    }
    try {
        const respuesta = await fetch("/sesion", {
            headers: { "Authorization": "Bearer " + token, "Accept": "application/json" },
            cache: "no-store"
        });
        if (respuesta.status === 401) {
            volverAlLogin();
            return;
        }
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.mensaje || "No se pudo comprobar la sesión.");
        document.getElementById("nombre-usuario").textContent = datos.usuario.nombre;
        document.getElementById("rol-usuario").textContent = "Rol: " + datos.usuario.rol;
        estado.textContent = "Sesión iniciada correctamente.";
        datosSesion.hidden = false;
    } catch (error) {
        estado.textContent = "No se pudo comprobar tu sesión. Recarga la página para intentar nuevamente.";
        volverLogin.hidden = false;
    }
}

cerrarSesion.addEventListener("click", async function () {
    cerrarSesion.disabled = true;
    mensaje.textContent = "";
    try {
        const respuesta = await fetch("/logout", {
            method: "POST",
            headers: { "Authorization": "Bearer " + sessionStorage.getItem("token"), "Accept": "application/json" }
        });
        if (respuesta.ok || respuesta.status === 401) {
            volverAlLogin();
            return;
        }
        const datos = await respuesta.json();
        throw new Error(datos.mensaje || "No se pudo cerrar la sesión.");
    } catch (error) {
        mensaje.className = "mensaje error";
        mensaje.textContent = "No se pudo cerrar la sesión. Intenta nuevamente.";
        cerrarSesion.disabled = false;
    }
});

// Revalida también al volver desde el historial del navegador.
window.addEventListener("pageshow", function () {
    datosSesion.hidden = true;
    consultarSesion();
});

