document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-reservaciones");
  const tabla = document.getElementById("tabla-reservaciones").querySelector("tbody");
  const clienteInput = document.getElementById("res-cliente");
  const mesaInput = document.getElementById("res-mesa");
  const btnInsertar = form.querySelector("button[type='submit']");
  const btnModificar = form.querySelector(".btn-warning");
  const btnEliminar = form.querySelector(".btn-secondary");
  const btnActualizar = form.querySelector(".btn-danger");
  let editando = false;
  let idEditando = null;

  // se reemplaza inputs por select dinamico
  const clienteSelect = document.createElement("select");
  clienteSelect.id = "res-cliente";
  clienteSelect.className = "form-control";
  clienteInput.replaceWith(clienteSelect);

  const mesaSelect = document.createElement("select");
  mesaSelect.id = "res-mesa";
  mesaSelect.className = "form-control";
  mesaInput.replaceWith(mesaSelect);

  // Inicialización de componentes
  cargarClientes();
  cargarMesas();
  cargarTabla();

  btnActualizar.addEventListener("click", () => cargarTabla());

  // insertar y modificar
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    if (!validarFormulario()) return;

    const datos = new FormData(form);
    datos.append("accion", editando ? "modificar" : "insertar");
    if (editando) datos.append("id_reservacion", idEditando);

    fetch("../crud/reservaciones_crud.php", { method: "POST", body: datos })
      .then(r => r.text())
      .then(res => {
        if (res === "ok") {
          alert(editando ? "Reservación actualizada correctamente" : "Reservación agregada correctamente");
          form.reset();
          cargarTabla();
          editando = false;
          idEditando = null;
          btnInsertar.textContent = "Insertar";
        } else {
          alert("Error en la operación. Revisa los datos.");
        }
      });
  });

  // validaciones contra sentencias sql
  function validarFormulario() {
    let valid = true;
    const regexSQL = /['";=<>*%]/; // bloquea caracteres peligrosos

    form.querySelectorAll("input, select").forEach(input => {
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

  // carga clientes y mesas
  function cargarClientes() {
    fetch("../HTML/reservaciones_crud.php", {
      method: "POST",
      body: new URLSearchParams({ accion: "clientes" }),
    })
      .then(r => r.json())
      .then(clientes => {
        clienteSelect.innerHTML = "<option value=''>Seleccionar...</option>";
        clientes.forEach(c => {
          clienteSelect.innerHTML += `<option value="${c.id_cliente}">${c.nombre}</option>`;
        });
      });
  }

  function cargarMesas() {
    fetch("../HTML/reservaciones_crud.php", {
      method: "POST",
      body: new URLSearchParams({ accion: "mesas" }),
    })
      .then(r => r.json())
      .then(mesas => {
        mesaSelect.innerHTML = "<option value=''>Seleccionar...</option>";
        mesas.forEach(m => {
          mesaSelect.innerHTML += `<option value="${m.id_mesa}">${m.descripcion}</option>`;
        });
      });
  }

  // carga la tabla para mostrarla con boton de modificar y eliminar
  function cargarTabla() {
    fetch("../HTML/reservaciones_crud.php", {
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
          // Eventos de los botones
          tr.querySelector(".btn-editar").addEventListener("click", () => editarFila(row));
          tr.querySelector(".btn-eliminar").addEventListener("click", () => eliminarFila(row.id_reservacion));
          tbody.appendChild(tr);
        });
      });
  }

  // metodo para editar o actualizar
  function editarFila(row) {
    document.getElementById("res-personas").value = row.cantidad_personas;
    document.getElementById("res-estado").value = row.estado;
    clienteSelect.value = row.id_cliente;
    mesaSelect.value = row.id_mesa;
    idEditando = row.id_reservacion;
    editando = true;
    btnInsertar.textContent = "Guardar Cambios";
    alert(`Editando reservación #${row.id_reservacion}`);
  }

  // metodo para eliminar segun id
  function eliminarFila(id) {
    if (!confirm(`¿Deseas eliminar la reservación #${id}? Esta acción no se puede deshacer.`)) return;

    const datos = new URLSearchParams();
    datos.append("accion", "eliminar");
    datos.append("id_reservacion", id);

    fetch("../HTML/reservaciones_crud.php", { method: "POST", body: datos })
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

