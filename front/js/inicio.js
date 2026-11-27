"use strict";

const estado = document.getElementById("estado-sesion");
const datosSesion = document.getElementById("datos-sesion");
const mensaje = document.getElementById("mensaje");
const cerrarSesion = document.getElementById("cerrar-sesion");
const volverLogin = document.getElementById("volver-login");
const actualizarMesas = document.getElementById("actualizar-mesas");
const mensajeMesas = document.getElementById("mensaje-mesas");
const listaMesas = document.getElementById("lista-mesas");
const filtroEstado = document.getElementById("filtro-estado");

const API_RESERVAS = "http://" + window.location.hostname + ":8001";
const nombresEstados = {
    disponible: "Disponible",
    reservada: "Reservada",
    ocupada: "Ocupada",
    fuera_servicio: "Fuera de servicio"
};
let mesasCargadas = [];
let cargandoMesas = false;

function volverAlLogin() {
    datosSesion.hidden = true;
    sessionStorage.removeItem("token");
    window.location.replace("/");
}

function mostrarMesas() {
    const mesas = mesasCargadas.filter(mesa => filtroEstado.value === "" || mesa.estado === filtroEstado.value);
    listaMesas.replaceChildren();
    for (const mesa of mesas) {
        const tarjeta = document.createElement("article");
        tarjeta.className = "mesa";
        const nombre = document.createElement("h3");
        nombre.textContent = mesa.numero;
        const capacidad = document.createElement("p");
        capacidad.textContent = "Capacidad: " + mesa.capacidad + " personas";
        const estadoMesa = document.createElement("span");
        const conocido = Object.hasOwn(nombresEstados, mesa.estado);
        estadoMesa.className = "estado-mesa " + (conocido ? "estado-" + mesa.estado : "");
        estadoMesa.textContent = conocido ? nombresEstados[mesa.estado] : "Sin estado";
        tarjeta.append(nombre, capacidad, estadoMesa);
        listaMesas.append(tarjeta);
    }

    mensajeMesas.className = "descripcion";
    if (mesasCargadas.length === 0) {
        mensajeMesas.textContent = "Todavía no hay mesas registradas.";
    } else if (mesas.length === 0) {
        mensajeMesas.textContent = "No hay mesas con ese estado.";
    } else {
        mensajeMesas.textContent = "Mostrando " + mesas.length + " de " + mesasCargadas.length + " mesas.";
    }
}

async function cargarMesas() {
    if (cargandoMesas) return;
    const token = sessionStorage.getItem("token");
    if (!token) {
        volverAlLogin();
        return;
    }
    cargandoMesas = true;
    actualizarMesas.disabled = true;
    filtroEstado.disabled = true;
    mesasCargadas = [];
    listaMesas.replaceChildren();
    mensajeMesas.textContent = "Cargando mesas...";
    mensajeMesas.className = "descripcion";

    try {
        const respuesta = await fetch(API_RESERVAS + "/mesas", {
            headers: { "Authorization": "Bearer " + token }
        });
        if (sessionStorage.getItem("token") !== token) return;
        if (respuesta.status === 401) {
            volverAlLogin();
            return;
        }
        if (!respuesta.ok) throw new Error("No se pudieron cargar las mesas.");
        const datos = await respuesta.json();
        if (!Array.isArray(datos.mesas)) throw new Error("Respuesta inválida.");
        mesasCargadas = datos.mesas;
        mostrarMesas();
    } catch (error) {
        if (!datosSesion.hidden) {
            mensajeMesas.className = "mensaje error";
            mensajeMesas.textContent = "No se pudieron cargar las mesas. Intenta nuevamente.";
        }
    } finally {
        cargandoMesas = false;
        actualizarMesas.disabled = false;
        filtroEstado.disabled = mesasCargadas.length === 0;
    }
}

async function consultarSesion() {
    const token = sessionStorage.getItem("token");
    if (!token) {
        volverAlLogin();
        return;
    }
    try {
        const respuesta = await fetch("/sesion", {
            headers: { "Authorization": "Bearer " + token }
        });
        if (sessionStorage.getItem("token") !== token) return;
        if (respuesta.status === 401) {
            volverAlLogin();
            return;
        }
        if (!respuesta.ok) throw new Error("No se pudo comprobar la sesión.");
        const datos = await respuesta.json();
        document.getElementById("nombre-usuario").textContent = datos.usuario.nombre;
        document.getElementById("rol-usuario").textContent = "Rol: " + datos.usuario.rol;
        estado.textContent = "Sesión iniciada correctamente.";
        volverLogin.hidden = true;
        datosSesion.hidden = false;
        await cargarMesas();
    } catch (error) {
        estado.textContent = "No se pudo comprobar tu sesión. Recarga la página.";
        volverLogin.hidden = false;
    }
}

actualizarMesas.addEventListener("click", cargarMesas);
filtroEstado.addEventListener("change", mostrarMesas);
cerrarSesion.addEventListener("click", async function () {
    cerrarSesion.disabled = true;
    mensaje.textContent = "";
    try {
        const respuesta = await fetch("/logout", {
            method: "POST",
            headers: { "Authorization": "Bearer " + sessionStorage.getItem("token") }
        });
        if (!respuesta.ok && respuesta.status !== 401) throw new Error("No se pudo cerrar la sesión.");
        volverAlLogin();
    } catch (error) {
        mensaje.className = "mensaje error";
        mensaje.textContent = "No se pudo cerrar la sesión. Intenta nuevamente.";
    } finally {
        cerrarSesion.disabled = false;
    }
});

window.addEventListener("pageshow", function () {
    datosSesion.hidden = true;
    listaMesas.replaceChildren();
    mesasCargadas = [];
    filtroEstado.disabled = true;
    consultarSesion();
});

