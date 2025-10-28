document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-reservaciones");
  const clienteInput = document.getElementById("res-cliente");
  const mesaInput = document.getElementById("res-mesa");
  const btnInsertar = form.querySelector("button[type='submit']");
  const btnActualizar = form.querySelector(".btn-danger");

  let editando = false;
  let idEditando = null;
  let mesasDisponibles = [];

  // Reemplazar inputs por selects
  const clienteSelect = document.createElement("select");
  clienteSelect.id = "res-cliente";
  clienteSelect.name = "id_cliente";
  clienteSelect.className = "form-control";
  clienteInput.replaceWith(clienteSelect);

  const mesaSelect = document.createElement("select");
  mesaSelect.id = "res-mesa";
  mesaSelect.name = "id_mesa";
  mesaSelect.className = "form-control";
  mesaInput.replaceWith(mesaSelect);

  cargarClientes();
  cargarMesas();           // carga mesas DISPONIBLES (sin incluir ninguna)
  cargarTabla();
  cargarSiguienteId();

  // Insertar o modificar
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!validarFormulario()) return;

    const datos = new FormData(form);
    datos.append("accion", editando ? "modificar" : "insertar");
    if (editando) datos.append("id_reservacion", idEditando);

    const resp = await fetch("../reservaciones_crud.php", { method: "POST", body: datos });
    const res = await resp.json();

    if (res.status === "ok") {
      Swal.fire({ icon: "success", title: editando ? "Reservación actualizada" : "Reservación agregada", timer: 1500, showConfirmButton: false });
      form.reset();
      editando = false;
      idEditando = null;
      btnInsertar.textContent = "Insertar";
      await cargarTabla();
      await cargarMesas();       // al terminar, sólo mesas disponibles
      await cargarSiguienteId();
    } else {
      Swal.fire({ icon: "error", title: "Error", text: res.msg || "Verifica los datos ingresados." });
    }
  });

  // Clientes
  function cargarClientes() {
    fetch("../reservaciones_crud.php", {
      method: "POST",
      body: new URLSearchParams({ accion: "clientes" }),
    })
      .then(r => r.json())
      .then(res => {
        if (res.status === "ok") {
          clienteSelect.innerHTML = "<option value=''>Seleccionar...</option>";
          res.data.forEach(c => {
            clienteSelect.innerHTML += `<option value="${c.id_cliente}">${c.nombre}</option>`;
          });
        }
      });
  }

  // Mesas: disponibles + includeId opcional
  function cargarMesas(includeId = null) {
    const params = new URLSearchParams({ accion: "mesas" });
    if (includeId) params.append("include_id", includeId);

    return fetch("../reservaciones_crud.php", {
      method: "POST",
      body: params,
    })
      .then(r => r.json())
      .then(res => {
        if (res.status === "ok") {
          mesaSelect.innerHTML = "<option value=''>Seleccionar...</option>";
          mesasDisponibles = res.data.map(m => ({
            id_mesa: parseInt(m.id_mesa),
            descripcion: m.descripcion,
            capacidad_personas: parseInt(m.capacidad_personas),
            estado: m.estado
          }));
          mesasDisponibles.forEach(m => {
            mesaSelect.innerHTML += `<option value="${m.id_mesa}">${m.descripcion}${m.estado !== 'DISPONIBLE' ? ' - ('+m.estado+')' : ''}</option>`;
          });
        }
      });
  }

  // Validación de capacidad
  function validarFormulario() {
    const cantidad = parseInt(document.getElementById("res-personas").value || "0", 10);
    const idMesa = parseInt(mesaSelect.value || "0", 10);
    if (!idMesa || !cantidad) return false;

    const mesaSel = mesasDisponibles.find(m => m.id_mesa === idMesa);
    if (mesaSel && cantidad > mesaSel.capacidad_personas) {
      Swal.fire({ icon: "warning", title: "Capacidad excedida", text: `La mesa seleccionada solo admite ${mesaSel.capacidad_personas} personas.` });
      return false;
    }
    return true;
    }

  // Tabla
  function cargarTabla() {
    return fetch("../reservaciones_crud.php", {
      method: "POST",
      body: new URLSearchParams({ accion: "listar" }),
    })
      .then(r => r.json())
      .then(res => {
        const tbody = document.querySelector("#tabla-reservaciones tbody");
        tbody.innerHTML = "";
        if (res.status === "ok") {
          res.data.forEach(row => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
              <td>${row.id_reservacion}</td>
              <td>${row.cliente_nombre}</td>
              <td>${row.mesa_desc}</td>
              <td>${row.cantidad_personas}</td>
              <td>${row.fecha_hora}</td>
              <td>${row.estado}</td>
              <td class="text-center">
                <button class="btn btn-sm btn-primary btn-editar">✏️</button>
                <button class="btn btn-sm btn-danger btn-eliminar">🗑️</button>
              </td>
            `;
            tr.querySelector(".btn-editar").addEventListener("click", () => editarFila(row));
            tr.querySelector(".btn-eliminar").addEventListener("click", () => eliminarFila(row.id_reservacion));
            tbody.appendChild(tr);
          });
        }
      });
  }

  // Editar: incluir mesa actual aunque esté reservada
  function editarFila(row) {
    // Cargar mesas: DISPONIBLES + incluir la mesa de este registro
    cargarMesas(row.id_mesa).then(() => {
      document.getElementById("res-id").value = row.id_reservacion;
      document.getElementById("res-personas").value = row.cantidad_personas;
      document.getElementById("res-fecha").value = row.fecha_hora.replace(" ", "T");
      document.getElementById("res-estado").value = row.estado;
      clienteSelect.value = row.id_cliente;
      mesaSelect.value = row.id_mesa;

      idEditando = row.id_reservacion;
      editando = true;
      btnInsertar.textContent = "Guardar Cambios";

      Swal.fire({ icon: "info", title: `Editando reservación #${row.id_reservacion}`, timer: 1200, showConfirmButton: false });
    });
  }

  // Eliminar
  function eliminarFila(id) {
    Swal.fire({
      title: `¿Eliminar reservación #${id}?`,
      text: "Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then(result => {
      if (result.isConfirmed) {
        const datos = new URLSearchParams();
        datos.append("accion", "eliminar");
        datos.append("id_reservacion", id);

        fetch("../reservaciones_crud.php", { method: "POST", body: datos })
          .then(r => r.json())
          .then(res => {
            if (res.status === "ok") {
              Swal.fire({ icon: "success", title: "Reservación eliminada", timer: 1300, showConfirmButton: false });
              cargarTabla();
              cargarMesas();     // vuelve a mostrar sólo disponibles
              cargarSiguienteId();
              if (editando) { // si estabas editando, cancela
                form.reset();
                editando = false;
                idEditando = null;
                btnInsertar.textContent = "Insertar";
              }
            } else {
              Swal.fire({ icon: "error", title: "Error al eliminar", text: res.msg || "Intenta nuevamente." });
            }
          });
      }
    });
  }

  // Refrescar
  btnActualizar.addEventListener("click", () => {
    form.reset();
    editando = false;
    idEditando = null;
    btnInsertar.textContent = "Insertar";
    cargarTabla();
    cargarMesas();
    cargarSiguienteId();

    Swal.fire({
      icon: "info",
      title: "Formulario reiniciado",
      text: "La tabla ha sido actualizada y el formulario se limpió.",
      timer: 1500,
      showConfirmButton: false,
    });
  });

});

// Siguiente ID
function cargarSiguienteId() {
  fetch("../reservaciones_crud.php", {
    method: "POST",
    body: new URLSearchParams({ accion: "siguiente_id" }),
  })
    .then(r => r.json())
    .then(res => {
      if (res.status === "ok") {
        document.getElementById("res-id").value = res.siguiente;
      }
    });
}

