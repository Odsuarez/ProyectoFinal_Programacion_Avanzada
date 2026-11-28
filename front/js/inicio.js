"use strict";

const apiReservas = "http://" + window.location.hostname + ":8001";
const token = sessionStorage.getItem("token");
const panel = document.getElementById("datos-sesion");
const formMesa = document.getElementById("form-mesa");
const formReserva = document.getElementById("form-reserva");
const filtros = document.getElementById("filtros-reservas");
const listaMesas = document.getElementById("lista-mesas");
const listaReservas = document.getElementById("lista-reservas");
const mensajeMesas = document.getElementById("mensaje-mesas");
const mensajeReservas = document.getElementById("mensaje-reservas");
const mensajeDisponibilidad = document.getElementById("mensaje-disponibilidad");
const filtroMesa = document.getElementById("filtro-estado");
const estadoReservas = document.getElementById("estado-reservas");

let mesas = [];
let mesaEnEdicion = null;
let reservaEnEdicion = null;

function volverAlLogin() {
    panel.hidden = true;
    sessionStorage.removeItem("token");
    window.location.replace("/");
}

async function pedir(url, metodo = "GET", datos) {
    const opciones = {
        method: metodo,
        headers: { Authorization: "Bearer " + token }
    };
    if (datos !== undefined) {
        opciones.headers["Content-Type"] = "application/json";
        opciones.body = JSON.stringify(datos);
    }

    const respuesta = await fetch(url, opciones);
    if (respuesta.status === 401) {
        volverAlLogin();
        throw new Error("La sesión ya no está activa.");
    }

    const resultado = await respuesta.json();
    if (!respuesta.ok) {
        throw new Error(resultado.mensaje || "No se pudo completar la solicitud.");
    }
    return resultado;
}

function texto(contenedor, etiqueta, contenido) {
    const elemento = document.createElement(etiqueta);
    elemento.textContent = contenido;
    contenedor.append(elemento);
    return elemento;
}

function boton(contenedor, nombre, accion) {
    const elemento = texto(contenedor, "button", nombre);
    elemento.type = "button";
    elemento.className = "boton-secundario";
    elemento.onclick = async function () {
        elemento.disabled = true;
        try {
            await accion();
        } finally {
            elemento.disabled = false;
        }
    };
    return elemento;
}

function tarjeta(lista, titulo) {
    const elemento = document.createElement("article");
    elemento.className = "mesa";
    texto(elemento, "h3", titulo);
    lista.append(elemento);
    return elemento;
}

function opcionesDeMesa(opciones) {
    const campo = formReserva.elements.mesa_id;
    const seleccion = campo.value;
    campo.replaceChildren();
    const inicial = texto(campo, "option", "Selecciona una mesa");
    inicial.value = "";
    for (const mesa of opciones) {
        const opcion = texto(campo, "option", mesa.numero + " (" + mesa.capacidad + " personas)");
        opcion.value = mesa.id;
    }
    campo.value = seleccion;
    if (campo.selectedIndex === -1) {
        campo.value = "";
    }
}

function mostrarMesas() {
    listaMesas.replaceChildren();
    for (const mesa of mesas) {
        if (filtroMesa.value && filtroMesa.value !== mesa.estado) {
            continue;
        }
        const elemento = tarjeta(listaMesas, mesa.numero);
        texto(elemento, "p", "Capacidad: " + mesa.capacidad + " personas");
        const estado = texto(elemento, "span", mesa.estado.replace("_", " "));
        estado.className = "estado-mesa estado-" + mesa.estado;
        const acciones = texto(elemento, "div", "");
        acciones.className = "acciones";
        boton(acciones, "Editar mesa", function () {
            mesaEnEdicion = mesa.id;
            formMesa.elements.numero.value = mesa.numero;
            formMesa.elements.numero.readOnly = true;
            formMesa.elements.capacidad.value = mesa.capacidad;
            formMesa.elements.estado.value = mesa.estado;
            document.getElementById("titulo-form-mesa").textContent = "Editar mesa " + mesa.numero;
            document.getElementById("salir-mesa").hidden = false;
            formMesa.elements.capacidad.focus();
        });
    }
    if (!listaMesas.children.length) {
        texto(listaMesas, "p", "No hay mesas para mostrar.");
    }
}

async function cargarMesas() {
    try {
        const datos = await pedir(apiReservas + "/mesas");
        mesas = datos.mesas;
        mostrarMesas();
        opcionesDeMesa(mesas);
        mensajeDisponibilidad.textContent = "";
        filtroMesa.disabled = false;
    } catch (error) {
        mensajeMesas.textContent = error.message;
    }
}

function nuevaMesa() {
    mesaEnEdicion = null;
    formMesa.reset();
    formMesa.elements.numero.readOnly = false;
    document.getElementById("titulo-form-mesa").textContent = "Nueva mesa";
    document.getElementById("salir-mesa").hidden = true;
}

formMesa.onsubmit = async function (evento) {
    evento.preventDefault();
    const guardar = evento.submitter;
    guardar.disabled = true;
    const campos = formMesa.elements;
    const datos = { capacidad: Number(campos.capacidad.value), estado: campos.estado.value };
    let ruta = "/mesas";
    let metodo = "POST";
    if (mesaEnEdicion === null) {
        datos.numero = campos.numero.value.trim();
    } else {
        ruta += "/" + mesaEnEdicion;
        metodo = "PATCH";
    }
    try {
        const resultado = await pedir(apiReservas + ruta, metodo, datos);
        nuevaMesa();
        mensajeMesas.textContent = resultado.mensaje;
        await cargarMesas();
    } catch (error) {
        mensajeMesas.textContent = error.message;
    } finally {
        guardar.disabled = false;
    }
};

function nuevaReserva() {
    reservaEnEdicion = null;
    formReserva.reset();
    formReserva.elements.nombre_cliente.readOnly = false;
    formReserva.elements.telefono_cliente.readOnly = false;
    opcionesDeMesa(mesas);
    mensajeDisponibilidad.textContent = "";
    document.getElementById("titulo-form-reserva").textContent = "Nueva reserva";
    document.getElementById("salir-reserva").hidden = true;
}

function editarReserva(reserva) {
    reservaEnEdicion = reserva;
    const campos = formReserva.elements;
    campos.nombre_cliente.value = reserva.nombre_cliente;
    campos.telefono_cliente.value = reserva.telefono_cliente;
    campos.nombre_cliente.readOnly = true;
    campos.telefono_cliente.readOnly = true;
    campos.fecha.value = reserva.fecha;
    campos.hora.value = reserva.hora;
    campos.cantidad_personas.value = reserva.cantidad_personas;
    campos.observaciones.value = reserva.observaciones || "";
    opcionesDeMesa(mesas);
    campos.mesa_id.value = reserva.mesa_id;
    mensajeDisponibilidad.textContent = "";
    document.getElementById("titulo-form-reserva").textContent = "Editar reserva de " + reserva.nombre_cliente;
    document.getElementById("salir-reserva").hidden = false;
    campos.fecha.focus();
}

function horarioReserva() {
    const campos = formReserva.elements;
    return {
        fecha: campos.fecha.value,
        hora: campos.hora.value,
        cantidad_personas: Number(campos.cantidad_personas.value)
    };
}

document.getElementById("buscar-mesas").onclick = async function () {
    const campos = formReserva.elements;
    if (!campos.fecha.reportValidity() || !campos.hora.reportValidity()
        || !campos.cantidad_personas.reportValidity()) {
        return;
    }
    const horario = horarioReserva();
    const consulta = new URLSearchParams(horario).toString();
    const botonBuscar = this;
    botonBuscar.disabled = true;
    try {
        const datos = await pedir(apiReservas + "/mesas/disponibles?" + consulta);
        if (consulta !== new URLSearchParams(horarioReserva()).toString()) {
            return;
        }
        // La consulta general también cuenta la propia reserva como ocupada.
        if (reservaEnEdicion && horario.fecha === reservaEnEdicion.fecha
            && (horario.hora === reservaEnEdicion.hora || horario.hora + ":00" === reservaEnEdicion.hora)) {
            const mesaActual = mesas.find(function (mesa) {
                return Number(mesa.id) === reservaEnEdicion.mesa_id
                    && mesa.estado !== "fuera_servicio" && mesa.capacidad >= horario.cantidad_personas;
            });
            if (mesaActual && !datos.mesas.some(function (mesa) { return Number(mesa.id) === Number(mesaActual.id); })) {
                datos.mesas.push(mesaActual);
            }
        }
        opcionesDeMesa(datos.mesas);
        mensajeDisponibilidad.textContent = datos.mesas.length
            ? "Selecciona una de las mesas disponibles."
            : "No hay mesas disponibles para ese horario y cantidad de personas.";
    } catch (error) {
        mensajeDisponibilidad.textContent = error.message;
    } finally {
        botonBuscar.disabled = false;
    }
};

for (const nombre of ["fecha", "hora", "cantidad_personas"]) {
    formReserva.elements[nombre].oninput = function () {
        opcionesDeMesa(mesas);
        mensajeDisponibilidad.textContent = "";
    };
}

formReserva.onsubmit = async function (evento) {
    evento.preventDefault();
    const guardar = evento.submitter;
    guardar.disabled = true;
    const campos = formReserva.elements;
    const datos = horarioReserva();
    datos.mesa_id = Number(campos.mesa_id.value);
    datos.observaciones = campos.observaciones.value.trim() || null;
    let ruta = "/reservas";
    let metodo = "POST";
    if (reservaEnEdicion) {
        ruta += "/" + reservaEnEdicion.id;
        metodo = "PATCH";
    } else {
        datos.nombre_cliente = campos.nombre_cliente.value.trim();
        datos.telefono_cliente = campos.telefono_cliente.value.trim();
    }
    try {
        const resultado = await pedir(apiReservas + ruta, metodo, datos);
        nuevaReserva();
        mensajeReservas.textContent = resultado.mensaje;
        await cargarReservas();
    } catch (error) {
        mensajeReservas.textContent = error.message;
    } finally {
        guardar.disabled = false;
    }
};

async function cambiarReserva(reserva, estado) {
    if (estado === "cancelada" && !window.confirm("¿Cancelar esta reserva?")) {
        return;
    }
    const ruta = apiReservas + "/reservas/" + reserva.id;
    try {
        let resultado;
        if (estado === "cancelada") {
            resultado = await pedir(ruta + "/cancelar", "POST");
        } else {
            resultado = await pedir(ruta + "/estado", "PATCH", { estado: estado });
        }
        if (reservaEnEdicion && reservaEnEdicion.id === reserva.id) {
            nuevaReserva();
        }
        mensajeReservas.textContent = resultado.mensaje;
        await cargarReservas();
    } catch (error) {
        mensajeReservas.textContent = error.message;
    }
}

async function cargarReservas() {
    const consulta = new URLSearchParams();
    for (const nombre of ["fecha", "cliente", "estado"]) {
        const valor = filtros.elements[nombre].value.trim();
        if (valor) {
            consulta.set(nombre, valor);
        }
    }
    estadoReservas.textContent = "Cargando reservas…";
    try {
        const datos = await pedir(apiReservas + "/reservas?" + consulta.toString());
        listaReservas.replaceChildren();
        estadoReservas.textContent = datos.reservas.length ? "" : "No hay reservas para mostrar.";
        for (const reserva of datos.reservas) {
            const elemento = tarjeta(listaReservas, reserva.nombre_cliente);
            texto(elemento, "p", reserva.fecha + " · " + reserva.hora);
            texto(elemento, "p", "Mesa: " + (reserva.mesa ? reserva.mesa.numero : reserva.mesa_id)
                + " · " + reserva.cantidad_personas + " personas");
            texto(elemento, "p", "Teléfono: " + reserva.telefono_cliente);
            texto(elemento, "p", "Estado: " + reserva.estado);
            if (reserva.observaciones) {
                texto(elemento, "p", "Observaciones: " + reserva.observaciones);
            }
            if (reserva.estado === "pendiente" || reserva.estado === "confirmada") {
                const acciones = texto(elemento, "div", "");
                acciones.className = "acciones";
                boton(acciones, "Editar", function () { editarReserva(reserva); });
                if (reserva.estado === "pendiente") {
                    boton(acciones, "Confirmar", function () { return cambiarReserva(reserva, "confirmada"); });
                }
                boton(acciones, "Finalizar", function () { return cambiarReserva(reserva, "finalizada"); });
                boton(acciones, "Cancelar", function () { return cambiarReserva(reserva, "cancelada"); });
            }
        }
    } catch (error) {
        estadoReservas.textContent = error.message;
    }
}

async function iniciar() {
    if (!token || sessionStorage.getItem("token") !== token) {
        volverAlLogin();
        return;
    }
    const estado = document.getElementById("estado-sesion");
    try {
        const datos = await pedir("/sesion");
        document.getElementById("nombre-usuario").textContent = datos.usuario.nombre;
        document.getElementById("rol-usuario").textContent = "Rol: " + datos.usuario.rol;
        estado.textContent = "";
        panel.hidden = false;
        await cargarMesas();
        await cargarReservas();
    } catch (error) {
        estado.textContent = error.message;
        document.getElementById("volver-login").hidden = false;
    }
}

document.getElementById("actualizar-mesas").onclick = cargarMesas;
document.getElementById("salir-mesa").onclick = nuevaMesa;
document.getElementById("salir-reserva").onclick = nuevaReserva;
filtroMesa.onchange = mostrarMesas;
filtros.onsubmit = function (evento) {
    evento.preventDefault();
    cargarReservas();
};
document.getElementById("cerrar-sesion").onclick = async function () {
    this.disabled = true;
    try {
        await pedir("/logout", "POST");
        volverAlLogin();
    } catch (error) {
        document.getElementById("mensaje").textContent = error.message;
    } finally {
        this.disabled = false;
    }
};
window.addEventListener("pageshow", iniciar);
