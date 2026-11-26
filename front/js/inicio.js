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

// El frontend se abre desde ms-auth en el puerto 8000.
const API_RESERVAS = "http://" + window.location.hostname + ":8001";
const nombresEstados = {
    disponible: "Disponible",
    reservada: "Reservada",
    ocupada: "Ocupada",
    fuera_servicio: "Fuera de servicio"
};
let peticionMesas = null;
let revisionSesion = 0;
let mesasCargadas = [];

function cancelarCargaMesas() {
    if (peticionMesas) peticionMesas.abort();
}

function volverAlLogin() {
    cancelarCargaMesas();
    datosSesion.hidden = true;
    sessionStorage.removeItem("token");
    window.location.replace("/");
}

function mostrarMesas(mesas) {
    const mesasVisibles = mesas.filter(mesa => filtroEstado.value === "" || mesa.estado === filtroEstado.value);
    mensajeMesas.className = "descripcion";
    listaMesas.replaceChildren();
    for (const mesa of mesasVisibles) {
        const tarjeta = document.createElement("article");
        tarjeta.className = "mesa";
        const nombre = document.createElement("h3");
        nombre.textContent = mesa.numero;
        const capacidad = document.createElement("p");
        capacidad.textContent = "Capacidad: " + mesa.capacidad + " personas";
        const estadoMesa = document.createElement("span");
        const estadoConocido = Object.hasOwn(nombresEstados, mesa.estado);
        estadoMesa.className = "estado-mesa " + (estadoConocido ? "estado-" + mesa.estado : "");
        estadoMesa.textContent = estadoConocido ? nombresEstados[mesa.estado] : "Sin estado";
        tarjeta.append(nombre, capacidad, estadoMesa);
        listaMesas.append(tarjeta);
    }
    if (mesas.length === 0) {
        mensajeMesas.textContent = "Todavía no hay mesas registradas.";
    } else if (filtroEstado.value === "") {
        mensajeMesas.textContent = mesas.length + (mesas.length === 1 ? " mesa registrada." : " mesas registradas.");
    } else if (mesasVisibles.length === 0) {
        mensajeMesas.textContent = "No hay mesas con ese estado (0 de " + mesas.length + ").";
    } else {
        mensajeMesas.textContent = "Mostrando " + mesasVisibles.length + " de " + mesas.length + " mesas.";
    }
}

async function cargarMesas() {
    const token = sessionStorage.getItem("token");
    if (!token) {
        volverAlLogin();
        return;
    }
    cancelarCargaMesas();
    const cancelacion = new AbortController();
    peticionMesas = cancelacion;
    const limite = setTimeout(() => cancelacion.abort(), 10000);
    actualizarMesas.disabled = true;
    filtroEstado.disabled = true;
    mesasCargadas = [];
    listaMesas.replaceChildren();
    listaMesas.setAttribute("aria-busy", "true");
    mensajeMesas.className = "descripcion";
    mensajeMesas.textContent = "Cargando mesas...";
    try {
        const respuesta = await fetch(API_RESERVAS + "/mesas", {
            headers: { "Authorization": "Bearer " + token, "Accept": "application/json" },
            cache: "no-store",
            signal: cancelacion.signal
        });
        if (peticionMesas !== cancelacion) return;
        if (respuesta.status === 401) {
            volverAlLogin();
            return;
        }
        if (!respuesta.ok) throw new Error("No se pudieron consultar las mesas.");
        const datos = await respuesta.json();
        if (!Array.isArray(datos.mesas)) throw new Error("Respuesta inválida.");
        if (peticionMesas === cancelacion) {
            mesasCargadas = datos.mesas;
            mostrarMesas(mesasCargadas);
        }
    } catch (error) {
        if (peticionMesas === cancelacion && !datosSesion.hidden) {
            mensajeMesas.className = "mensaje error";
            mensajeMesas.textContent = "No se pudieron cargar las mesas. Intenta nuevamente con Actualizar mesas.";
        }
    } finally {
        clearTimeout(limite);
        if (peticionMesas === cancelacion) {
            peticionMesas = null;
            actualizarMesas.disabled = false;
            filtroEstado.disabled = mesasCargadas.length === 0;
            listaMesas.setAttribute("aria-busy", "false");
        }
    }
}

async function consultarSesion() {
    const revision = ++revisionSesion;
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
        if (revision !== revisionSesion) return;
        if (respuesta.status === 401) {
            volverAlLogin();
            return;
        }
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error("No se pudo comprobar la sesión.");
        if (revision !== revisionSesion) return;
        document.getElementById("nombre-usuario").textContent = datos.usuario.nombre;
        document.getElementById("rol-usuario").textContent = "Rol: " + datos.usuario.rol;
        estado.textContent = "Sesión iniciada correctamente.";
        volverLogin.hidden = true;
        cerrarSesion.disabled = false;
        datosSesion.hidden = false;
        await cargarMesas();
    } catch (error) {
        if (revision !== revisionSesion) return;
        estado.textContent = "No se pudo comprobar tu sesión. Recarga la página para intentar nuevamente.";
        volverLogin.hidden = false;
    }
}

actualizarMesas.addEventListener("click", cargarMesas);
filtroEstado.addEventListener("change", function () {
    mostrarMesas(mesasCargadas);
});

cerrarSesion.addEventListener("click", async function () {
    cerrarSesion.disabled = true;
    cancelarCargaMesas();
    mensaje.textContent = "";
    try {
        const respuesta = await fetch("/logout", {
            method: "POST",
            headers: { "Authorization": "Bearer " + sessionStorage.getItem("token"), "Accept": "application/json" }
        });
        if (respuesta.ok || respuesta.status === 401) {
            ++revisionSesion;
            volverAlLogin();
            return;
        }
        throw new Error("No se pudo cerrar la sesión.");
    } catch (error) {
        mensaje.className = "mensaje error";
        mensaje.textContent = "No se pudo cerrar la sesión. Intenta nuevamente.";
        cerrarSesion.disabled = false;
    }
});

window.addEventListener("pageshow", function () {
    datosSesion.hidden = true;
    cancelarCargaMesas();
    listaMesas.replaceChildren();
    mesasCargadas = [];
    filtroEstado.disabled = true;
    mensajeMesas.textContent = "";
    consultarSesion();
});

