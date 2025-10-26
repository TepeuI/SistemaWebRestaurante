document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-reservaciones");
  const clienteInput = document.getElementById("res-cliente");
  const mesaInput = document.getElementById("res-mesa");
  const btnInsertar = form.querySelector("button[type='submit']");
  const btnModificar = form.querySelector(".btn-warning");
  const btnEliminar = form.querySelector(".btn-secondary");
  const btnActualizar = form.querySelector(".btn-danger");
  let editando = false;
  let idEditando = null;

  // Se reemplazan inputs por selects dinámicos
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

  // Inicialización
  cargarClientes();
  cargarMesas();
  cargarTabla();
  cargarSiguienteId();


  btnActualizar.addEventListener("click", () => cargarTabla());

  // Insertar o Modificar
form.addEventListener("submit", (e) => {
  e.preventDefault();
  if (!validarFormulario()) return;

  const datos = new FormData(form);
  datos.append("accion", editando ? "modificar" : "insertar");
  if (editando) datos.append("id_reservacion", idEditando);

  fetch("../reservaciones_crud.php", { method: "POST", body: datos })
    .then(r => r.json()) // importante: usamos json() para poder acceder a res.status y res.msg
    .then(res => {
      if (res.status === "ok") {
        alert(editando ? "Reservación actualizada correctamente" : "Reservación agregada correctamente");
        form.reset();
        cargarTabla();
        editando = false;
        idEditando = null;
        btnInsertar.textContent = "Insertar";
      } else {
        alert("Error en la operación: " + (res.msg ?? "Revisa los datos."));
      }
    });
});


// Validación básica y anti-SQL
function validarFormulario() {
  let valid = true;
  const regexSQL = /['";=<>*%]/;

  form.querySelectorAll("input, select").forEach(input => {
    // ignora campos ocultos y el ID autoincremental
    if (input.type === "hidden" || input.id === "res-id") return;

    if (!input.value.trim()) {
      input.classList.add("is-invalid");
      valid = false;
    } else if (regexSQL.test(input.value)) {
      alert(`El campo "${input.id}" contiene caracteres no permitidos.`);
      input.classList.add("is-invalid");
      valid = false;
    } else {
      input.classList.remove("is-invalid");
    }
  });

  if (!valid) alert("Por favor, corrige los campos marcados en rojo.");
  return valid;
}


// Cargar clientes y mesas 
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
        // Convertimos el value a número para que bind_param reciba int
        clienteSelect.innerHTML += `<option value="${Number(c.id_cliente)}">${c.nombre}</option>`;
      });
    }
  });
}

// Cargar mesas dinámicamente
function cargarMesas() {
  fetch("../reservaciones_crud.php", {
    method: "POST",
    body: new URLSearchParams({ accion: "mesas" }),
  })
  .then(r => r.json())
  .then(res => {
    if (res.status === "ok") {
      mesaSelect.innerHTML = "<option value=''>Seleccionar...</option>";
      res.data.forEach(m => {
        // Convertimos el value a número
        mesaSelect.innerHTML += `<option value="${Number(m.id_mesa)}">${m.descripcion}</option>`;
      });
    }
  });
}



  // Cargar tabla
  function cargarTabla() {
    fetch("../reservaciones_crud.php", {
      method: "POST",
      body: new URLSearchParams({ accion: "listar" }),
    })
      .then(r => r.json())
      .then(data => {
        const tbody = document.querySelector("#tabla-reservaciones tbody");
        tbody.innerHTML = "";
        data.forEach(row => {
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
      });
  }

  // Editar reservación
  function editarFila(row) {
    document.getElementById("res-personas").value = row.cantidad_personas;
    document.getElementById("res-fecha").value = row.fecha_hora.replace(" ", "T");
    document.getElementById("res-estado").value = row.estado;
    clienteSelect.value = row.id_cliente;
    mesaSelect.value = row.id_mesa;
    idEditando = row.id_reservacion;
    editando = true;
    btnInsertar.textContent = "Guardar Cambios";
    alert(`Editando reservación #${row.id_reservacion}`);
  }

  // Eliminar reservación
  function eliminarFila(id) {
    if (!confirm(`¿Deseas eliminar la reservación #${id}? Esta acción no se puede deshacer.`)) return;

    const datos = new URLSearchParams();
    datos.append("accion", "eliminar");
    datos.append("id_reservacion", id);

    fetch("../reservaciones_crud.php", { method: "POST", body: datos })
      .then(r => r.text())
      .then(res => {
        if (res === "ok") {
          alert("Reservación eliminada correctamente");
          cargarTabla();
        } else {
          alert("Error al eliminar la reservación");
        }
      });
  }
});

function cargarSiguienteId() {
  fetch("../reservaciones_crud.php", {
    method: "POST",
    body: new URLSearchParams({ accion: "siguiente_id" }),
  })
    .then(r => r.json())
    .then(res => {
      if (res.status === "ok") {
        document.getElementById("res-id").value = res.siguiente;
      } else {
        console.error("Error al obtener siguiente ID:", res.msg);
      }
    })
    .catch(err => console.error("Error fetch siguiente_id:", err));
}

